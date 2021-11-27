<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\OrderEditType;
use App\Form\OrderType;
use App\Form\ShippingDetailsType;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/order")
 */
class OrderController extends AbstractController
{
    /**
     * @Route("/", name="order_index", methods={"GET"})
     */
    public function index(Request $request, OrderRepository $orderRepository): Response
    {
        $id = $request->query->get('id');
        $limit = $request->query->get('limit', 8);
        $page = $request->query->get('page', 1);

        if ($page <= 0) {
            $this->addFlash('danger', "Invalid page number");
            return $this->redirectToRoute('order_index');
        }
        if ($limit <= 1) {
            $this->addFlash('danger', "Limit should be more than 1");
            return $this->redirectToRoute('order_index');
        }

        $pageNum = $orderRepository->countPages($id, $limit);

        $orders = $orderRepository->filter($id, $limit, $page);

        if (!($orders) && in_array($page, range(1, $pageNum))) {
            throw new BadRequestHttpException('Error 400');
        }
        if ($page > $pageNum) {
            $this->addFlash('danger', "Invalid page number");
            return $this->redirectToRoute('order_index');
        }
        if ($limit > 100) {
            $this->addFlash('danger', "Limit exceeded");
            return $this->redirectToRoute('order_index');
        }

        return $this->render('order/index.html.twig', [
            'orders' => $orders,

            'currentValues' => [
                'limit' => $limit,
                'page' => $page,
                'id' => $id,
            ],

            'totalPages' => $pageNum
        ]);
    }

    /**
     * @Route("/new", name="order_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $order = new Order();
        $order->setStatus('New');
        $order->setUser($this->getUser());

        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($order);
            $entityManager->flush();

            return $this->redirectToRoute('order_index');
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();

        return $this->render('order/new.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="order_show", methods={"GET"})
     */
    public function show(Order $order): Response
    {
        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="order_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Order $order): Response
    {
        $form = $this->createForm(OrderEditType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('order_show',['id'=>$order->getId()]);
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="order_delete", methods={"POST"})
     */
    public function delete(Request $request, Order $order): Response
    {
        if ($this->isCsrfTokenValid('delete' . $order->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($order);
            $entityManager->flush();
        }

        return $this->redirectToRoute('order_index');
    }

    /**
     * @Route("/{id}/nextstatus", name="next_status", methods={"GET","POST"})
     */
    public function setNextStatus(Order $order, Request $request)
    {
        $status = $order->getStatus();

        $searchId = $request->get('id');
        $page = $request->get('page');
        $limit = $request->get('limit');

        switch ($status){
            case "New":
                $order->setStatus('In Progress');
                 $this->forward('App\Controller\MailerController::sendEmail', [
                     'order' => $order
                ]);
                break;

            case "In Progress":
                $order->setStatus('Sent');
                $this->forward('App\Controller\MailerController::sendEmail', [
                    'order' => $order
                ]);
                break;

            case "Sent":
                $order->setStatus('Closed');
                $this->forward('App\Controller\MailerController::sendEmail', [
                    'order' => $order
                ]);
                break;
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($order);
        $em->flush();

        return $this->redirectToRoute('order_index', [
            'page' => $page,
            'limit' => $limit,
            'searchId' => $searchId]);
    }


}
