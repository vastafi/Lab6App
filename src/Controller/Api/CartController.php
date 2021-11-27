<?php


namespace App\Controller\Api;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\Product;
use App\Form\CheckoutType;
use App\Form\OrderType;
use App\Repository\CartRepository;
use App\Response\ApiErrorResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/api/v1/cart")
 */
class CartController extends AbstractController
{
    /**
     * @Route("/", name="cart_index", methods={"GET"})
     * @param CartRepository $cartRepository
     * @return JsonResponse|Response
     */
    public function index(CartRepository $cartRepository)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $cart = $cartRepository->findOneBy(["user"=>$this->getUser()->getId()]);
        if($cart){
            $productRepository = $this->getDoctrine()->getRepository(Product::class);
            $products = $productRepository->findBy(['code' => array_column($cart->getItems(), 'code')]);
            $cartItem = [];
            foreach ($products as $product){
                $cartItem [] = [
                    'product' => $product,
                    'amount' => array_column($cart->getItems(), 'amount', 'code')[$product->getCode()]
                ];
            }
            return $this->json($cartItem);
        }
        else{
            return new Response(null, 404);
        }
    }

    /**
     * @Route("/{productCode}", name="cart_add", methods={"POST"})
     * @param Request $request
     * @param string $productCode
     * @return Response
     */
    public function add(Request $request, string $productCode)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $em = $this->getDoctrine()->getManager();
        $cartRepository = $this->getDoctrine()->getRepository(Cart::class);
        $productRepository = $this->getDoctrine()->getRepository(Product::class);
        $product = $productRepository->findOneBy(['code'=>$productCode]);
        if(!$product)
        {
            return new Response(null, 404);
        }
        $amount = $request->query->get('amount', 1);
        if($product->getAvailableAmount() < $amount){
            return new ApiErrorResponse("1204", "We don't have so many products");
        }
        $user = $this->getUser();
        $cart = $cartRepository->findOneBy(["user"=>$user->getId()]);
        if($cart)
        {
            if($cart->addItem($productCode, $amount, $product->getAvailableAmount())){
                $cart->setUser($user);
            }
            else{
                return new ApiErrorResponse("1204", "We don't have so many products");
            }
        }
        else
        {
            $cart = new Cart();
            $cart->setItems([["code"=>$productCode, "amount"=>$amount]]);
            $cart->setUser($user);
        }
        $em->persist($cart);
        $em->flush();
        return new Response(null, 200);

    }

    /**
     * @Route("/{productCode}", name="cart_remove", methods={"DELETE"})
     * @param $productCode
     * @return Response
     */
    public function remove($productCode): Response
    {
        $em = $this->getDoctrine()->getManager();
        $cartRepository = $this->getDoctrine()->getRepository(Cart::class);
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        $cart = $cartRepository->findOneBy(["user"=>$user->getId()]);
        if($cart)
        {
            $cart->removeItem($productCode);
        }
        else {
            return new Response(null, 404);
        }
        $em->persist($cart);
        $em->flush();
        return new Response(null, 200);
    }

    /**
     * @Route("/", name="cart_update", methods={"PATCH"})
     * @param Request $request
     * @return Response
     */
    public function update(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $cartRepository = $this->getDoctrine()->getRepository(Cart::class);
        $productRepository = $this->getDoctrine()->getRepository(Product::class);
        $productCode = $request->query->get('code');
        $product = $productRepository->findOneBy(['code'=>$productCode]);
        if(!$product)
        {
            return new Response(null, 404);
        }
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $amount = $request->query->get('amount');
        if($amount <= 0){
            return new ApiErrorResponse("1210", "Amount can not be negative or zero");
        }
        $user = $this->getUser();
        $cart = $cartRepository->findOneBy(["user"=>$user->getId()]);
        if(!array_key_exists($productCode, array_column($cart->getItems(), 'amount', 'code'))){
            return new Response(null, 404);
        }
        if($cart)
        {
            if($product->getAvailableAmount() < $amount){
                return new ApiErrorResponse("1204", "We don't have so many products");
            }
            $cart->setAmount($productCode, $amount);
            $cart->setUser($user);
        }
        else{
            return new Response(null, 404);
        }
        $em->persist($cart);
        $em->flush();
        return new Response(null, 200);
    }

}