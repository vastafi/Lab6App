<?php


namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use DateTime;
use DateTimeZone;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * @Route("/admin")
 */
class AdminController extends AbstractController
{

    /**
     * @Route("/products", name="adminpr")
     * @param Request $request
     * @param ProductRepository $productRepository
     * @return Response
     */
    public function indexAdmin(Request $request, ProductRepository $productRepository): Response
    {
        $category=$request->query->get('category');
        $name=$request->query->get('name');
        $limit=$request->query->get('limit',8);
        $page=$request->query->get('page',1);
        $pageNum = $productRepository->countPages($category, $name, $limit);
        if($page <= 0){
            $this->addFlash('warning', "Invalid page number");
            return $this->redirectToRoute('adminpr');
        }
        if($limit <= 0){
            $this->addFlash('warning', "Limit can not be negative or zero");
            return $this->redirectToRoute('adminpr');
        }
        $products = $productRepository->filter($category, $name, $limit, $page);
        if(!($products) && in_array($page, range(1, $pageNum))){
            throw new BadRequestHttpException("400");
        }
        if($page > $pageNum){
            $this->addFlash('warning', "Invalid page number");
            return $this->redirectToRoute('adminpr');
        }
        if ($limit > 100) {
            $this->addFlash('warning', "Limit exceeded");
            return $this->redirectToRoute('adminpr');
        }
        return $this->render('admin/products.html.twig', [
            'products' => $products,
            'currentValues' => [
                'category' => $category,
                'limit' => $limit,
                'page' => $page,
                'name' => $name,
            ],
            'totalPages'=>$pageNum
        ]);
    }
    /**
     * @Route("/{productCode}", name="show", methods={"GET"}, requirements={"productCode":"[A][B]\d+"})
     * @param ProductRepository $productRepository
     * @param string $productCode
     * @return Response
     */
    public function show(ProductRepository $productRepository, string $productCode): Response
    {
        $product = $productRepository->findOneBy(['code' => $productCode]);
        return $this->render('admin/show.html.twig', [
            'product' => $product,
        ]);
    }

    /**
     * @Route("/{productCode}/edit", name="edit", methods={"GET","POST"}, requirements={"productCode":"[A][B]\d+"})
     * @param Request $request
     * @param ProductRepository $productRepository
     * @param string $productCode
     * @return Response
     * @throws Exception
     */
    public function edit(Request $request, ProductRepository $productRepository, string $productCode): Response
    {
        $product = $productRepository->findOneBy(['code' => $productCode]);
        $product->setUpdatedAt(new DateTime(null, new DateTimeZone('Europe/Athens')));
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('adminpr');
        }

        return $this->render('admin/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/delete", name="delete", methods={"GET","POST"})
     * @param Request $request
     * @param Product $product
     * @return Response
     */
    public function delete(Request $request, Product $product): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($product);
            $entityManager->flush();
        }
        return $this->redirectToRoute('adminpr');
    }
    /**
     * @Route("/create", name="pr_new", methods={"GET","POST"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function createProduct(Request $request): Response
    {
        $product = new Product();
        $product->setCreatedAt(new DateTime(null, new DateTimeZone('Europe/Athens')));
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        $repo = $this->getDoctrine()->getRepository(Product::class);
        if ($form->isSubmitted() && $form->isValid()) {
            $repo = $this->getDoctrine()->getRepository(Product::class);
            if ($repo->count(['code'=> $product->getCode()]) > 0){
                #code 400 bad request
                return $this->render('admin/new.html.twig', [
                    'errors' => ['A product with this code exists already!'],
                    'product' => $product,
                    'form' => $form->createView(),
                ]);

            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('adminpr');
        }

        return $this->render('admin/new.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }
}