<?php

namespace App\Controller;

use App\Entity\Image;
use App\Form\ImageEditType;
use App\Form\ImageType;
use App\Repository\ImageRepository;
use App\Repository\ProductRepository;
use DateTime;
use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/image/gallery")
 */
class ImageController extends AbstractController
{
    /**
     * @Route("/", name="image_index", methods={"GET"})
     * @param ImageRepository $imageRepository
     * @param Request $request
     * @return Response
     */
    public function index(ImageRepository $imageRepository,Request $request): Response
    {
        $tag = $request->query->get('tag');
        $limit = $request->query->get('limit', 8);
        $page = $request->query->get('page', 1);

        $pageNum = $imageRepository->countPages( $tag,$limit);
        if($page <= 0){
            $this->addFlash('warning', "Invalid page number");
            return $this->redirectToRoute('image_index');
        }
        if($limit <= 1){
            $this->addFlash('warning', "Limit can not be negative or zero");
            return $this->redirectToRoute('image_index');
        }
        $image = $imageRepository->filter($tag, $limit, $page);
        if(!($image) && in_array($page, range(1, $pageNum))){
            return $this->render('image/index.html.twig');
        }
        if($page > $pageNum){
            $this->addFlash('warning', "Invalid page number");
            return $this->redirectToRoute('image_index');
        }
        if ($limit > 100) {
            $this->addFlash('warning', "Limit exceeded");
            return $this->redirectToRoute('image_index');
        }
        return $this->render('image/index.html.twig', [
            'images' => $image,

            'currentValues' => [
                'limit' => $limit,
                'page' => $page,
                'tag' => $tag,
            ],

            'totalPages' => $pageNum
        ]);
    }

    /**
     * @Route("/fragment", name="gallery_fragment", methods={"GET"})
     * @param ImageRepository $imageRepository
     * @return Response
     */
    public function fragment(ImageRepository $imageRepository): Response
    {
        return $this->render('image/fragment.html.twig', [
            'images' => $imageRepository->findAll()
        ]);
    }

    private function uploadImageWithSecureName($form, $slugger): string
    {
        $imageFile = $form->get('path')->getData();
        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
        try {
            $gallery = $this->getParameter('gallery_path');
            $imageFile->move(
                $gallery,
                $newFilename
            );
        } catch (FileException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        return $newFilename;
    }

    private function checkTags(Image $image): array
    {
        $errors = [];
        foreach ($image->getTag() as $tag) {
            if (mb_strlen($tag) > 22 || mb_strlen($tag) < 2) {
                $errors['tagLen'] = "The length of each tag must be from 2 to 22 characters";
            }
            if (preg_match('/[^a-zа-я0-9 ]/', $tag)) {
                $errors['tagMatch'] = "The tags must contain only characters and digits";
            }
        }
        return $errors;
    }

    /**
     * @Route("/new", name="image_new", methods={"GET","POST"})
     * @param Request $request
     * @param SluggerInterface $slugger
     * @return Response
     */
    public function new(Request $request, SluggerInterface $slugger): Response
    {
        $image = new Image();
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */

            $errors = $this->checkTags($image);

            if (!empty($errors)) {
                return $this->render('image/new.html.twig', [
                    'errors' => $errors,
                    'image' => $image,
                    'form' => $form->createView(),
                ]);
            }
            $image->setPath($this->uploadImageWithSecureName($form, $slugger));
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($image);
            $entityManager->flush();
            return $this->redirectToRoute('image_index');
        }

        return $this->render('image/new.html.twig', [
            'image' => $image,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="image_show", methods={"GET"})
     * @param Image $image
     * @return Response
     */
    public function show(Image $image): Response
    {
        return $this->render('image/show.html.twig', [
            'image' => $image,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="image_edit", methods={"GET","POST"})
     * @param Request $request
     * @param Image $image
     * @param SluggerInterface $slugger
     * @return Response
     */
    public function edit(Request $request, Image $image, SluggerInterface $slugger): Response
    {
        $origPath = $image->getPath();
        $form = $this->createForm(ImageEditType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $this->checkTags($image);
            if (!empty($errors)) {
                return $this->render('image/edit.html.twig', [
                    'errors' => $errors,
                    'image' => $image,
                    'form' => $form->createView(),
                ]);
            }
            if ($image->getPath() === '% & # { } \\ / ! $ \' \" : < > @  * ? + ` | =') {
                $image->setPath($origPath);
            } else {

                $image->setPath($this->uploadImageWithSecureName($form, $slugger));
            }
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('image_index');
        }
        return $this->render('image/edit.html.twig', [
            'image' => $image,
            'form' => $form->createView(),
        ]);
    }
    public function deleteImageFromProducts(Image $image, ProductRepository $productRepository)
    {
        $products = $productRepository->findByImage($image);
        foreach ($products as $product) {
            $paths = $product->getProductImages();
            array_splice($paths, array_search($image->getPath(), $paths), 1);
            (count($paths) === 0) ? $product->setProductImages(null) : $product->setProductImages($paths);
            $date = new DateTime(null, new DateTimeZone('Europe/Athens'));
            $product->setUpdatedAt($date);
        }
    }

    /**
     * @Route("/{id}", name="image_delete", methods={"POST"})
     * @param Request $request
     * @param Image $image
     * @param ProductRepository $productRepository
     * @return Response
     */
    public function delete(Request $request, Image $image, ProductRepository $productRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$image->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $this->deleteImageFromProducts($image, $productRepository);
            $filesystem = new Filesystem();
            $gallery = $this->getParameter('gallery_path');
            $filesystem->remove($gallery .$image->getPath());
            $entityManager->remove($image);
            $entityManager->flush();
        }

        return $this->redirectToRoute('image_index');
    }

  }
