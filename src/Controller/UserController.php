<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserEditType;
use App\Form\UserType;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/admin/user")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/", name="user_index", methods={"GET"})
     * @param Request $request
     * @param UserRepository $userRepository
     * @return Response
     */
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $email = $request->query->get('email');
        $limit = $request->query->get('limit', 8);
        $page = $request->query->get('page', 1);

        $pageNum = $userRepository->countPages( $email,$limit);
        if($page <= 0){
            $this->addFlash('danger', "Invalid page number");
            return $this->redirectToRoute('user_index');
        }
        if($limit <= 1){
            $this->addFlash('danger', "Limit can not be negative or zero");
            return $this->redirectToRoute('user_index');
        }
        $users = $userRepository->filter( $email, $limit, $page);
        if(!($users) && in_array($page, range(1, $pageNum))){
            throw new BadRequestHttpException("400");
        }
        if($page > $pageNum){
            $this->addFlash('danger', "Invalid page number");
            return $this->redirectToRoute('user_index');
        }
        if ($limit > 100) {
            $this->addFlash('danger', "Limit exceeded");
            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/index.html.twig', [
            'users' => $users,

            'currentValues' => [
                'limit' => $limit,
                'page' => $page,
                'email' => $email,
            ],

            'totalPages' => $pageNum
        ]);
    }

    /**
     * @Route("/new", name="user_new", methods={"GET","POST"})
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     * @return Response
     */
    public function new(Request $request, UserPasswordEncoderInterface $encoder): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($encoder->encodePassword($user, $form->get('password')->getData()));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="user_show", methods={"GET"})
     * @param User $user
     * @return Response
     */
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="user_edit", methods={"GET","POST"})
     * @param Request $request
     * @param User $user
     * @return Response
     */
    public function edit(Request $request, User $user, UserPasswordEncoderInterface $encoder): Response
    {
        $form = $this->createForm(UserEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/delete", name="user_delete", methods={"POST","GET"})
     * @param Request $request
     * @param User $user
     * @return Response
     */
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('user_index');
    }
}
