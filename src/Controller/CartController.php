<?php


namespace App\Controller;


use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\Product;
use App\Form\CheckoutType;
use App\Form\OrderEditType;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use App\Response\ApiErrorResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/cart")
 */
class CartController extends AbstractController
{
    /**
     * @Route("/", name="cart")
     * @return Response
     */
    public function index()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('cart/cart.html.twig');
    }

    /**
     * @Route("/checkout", name="checkout", methods={"GET","POST"})
     */
    public function checkout(Request $request):Response{
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $em = $this->getDoctrine()->getManager();
        $cartRepository = $this->getDoctrine()->getRepository(Cart::class);
        $productRepository = $this->getDoctrine()->getRepository(Product::class);
        $user = $this->getUser();
        $cart = $cartRepository->findOneBy(["user"=>$user->getId()]);
        $items = [];
        $total = 0;
        if($cart) {
            $products = $productRepository->findBy(['code' => array_column($cart->getItems(), 'code')]);

            foreach ($products as $product) {
                $amount = array_column($cart->getItems(), 'amount', 'code')[$product->getCode()];
                if ($product->getAvailableAmount() < $amount) {
                    return new ApiErrorResponse('14068', 'We don\'t have such an amount for ' . $product->getName());
                }

                $items[] = ['code' => $product->getCode(),
                    'amount' => $amount,
                    'price' => $product->getPrice()];
                $total += $amount * $product->getPrice();
                $productsName[] = ['name' => $product->getName()];
            }

        }
        if (empty($items)){
            $this->addFlash('warning','Your cart is empty!');
            return $this->redirectToRoute('cart');
        }

        $order = $this->createOrder($items, $total);
        $form = $this->createForm(CheckoutType::class, $order);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $product->setAvailableAmount($product->getAvailableAmount() - $amount);
            $em->persist($product);
            $cartRepository->removeCart($cart->getId());
            $em->persist($order);
            $em->flush();

//            $this->addFlash('order_placed', 'Your order has been placed! Check your email to see more details.');

            $this->forward('App\Controller\MailerController::sendEmail', [
                'order' => $order
            ]);
            $this->addFlash('success', 'Your order has been placed! Check your email to see more details.');

            return $this->redirectToRoute('product_index');
        }

        $em->flush();

        return $this->render('order/checkout.html.twig', [
            'order' => $order,
            'productsName' => $productsName,
            'form' => $form->createView(),
        ]);

    }

    public function createOrder(array $items, float $total):Order{
        $order = new Order();
        $order->setItems($items);
        $order->setStatus('New');
        $order->setTotal($total);
        $order->setUser($this->getUser());
        return $order;
    }

    /**
     * @Route("/{id}/cancel", name="cancel_order", methods={"GET","POST"})
     */
    public function cancelOrder(Order $order): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        if ($order->getStatus() == 'Closed'){
            $this->addFlash('warning',"You can't cancel your order, because it's already closed.");
            return $this->redirectToRoute('product_index');
        }

        $order->setStatus('Canceled');

        $em = $this->getDoctrine()->getManager();
        $em->persist($order);
        $em->flush();

        $this->forward('App\Controller\MailerController::sendEmail', [
            'order' => $order
        ]);

        $this->addFlash('warning','Your order has been canceled.');
        return $this->redirectToRoute('product_index');

    }

    /**
     * @Route("/{id}/close", name="close_order", methods={"GET","POST"})
     */
    public function closeOrder(Order $order): \Symfony\Component\HttpFoundation\RedirectResponse
    {

        if ($order->getStatus() == 'Closed'){
            $this->addFlash('warning',"Your order is already closed.");
            return $this->redirectToRoute('product_index');
        }
        if ($order->getStatus() == 'Canceled'){
            $this->addFlash('warning',"You can't close this order because it's canceled.");
            return $this->redirectToRoute('product_index');
        }
        $order->setStatus('Closed');

        $em = $this->getDoctrine()->getManager();
        $em->persist($order);
        $em->flush();

        $this->forward('App\Controller\MailerController::sendEmail', [
            'order' => $order
        ]);

        $this->addFlash('success','Your order has been closed.');
        return $this->redirectToRoute('product_index');

    }

}