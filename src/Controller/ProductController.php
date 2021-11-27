<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * @Route("/products")
 * @method Exception(string $string)
 */
class ProductController extends AbstractController
{
    /**
     * @Route("/", name="product_index")
     * @param Request $request
     * @param ProductRepository $productRepository
     * @return Response
     * @throws Exception
     */
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $category = $request->query->get('category');
        $name = $request->query->get('name');
        $limit = $request->query->get('limit', 3);
        $page = $request->query->get('page', 1);


        if($page <= 0){
            $this->addFlash('danger', "Invalid page number");
            return $this->redirectToRoute('product_index');
        }
        if($limit <= 1){
            $this->addFlash('danger', "Limit can not be negative, zero or one");
            return $this->redirectToRoute('product_index');
        }
        $pageNum = $productRepository->countPages($category, $name, $limit);
        $products = $productRepository->filter($category, $name, $limit, $page);

        /* @note - удалёный участок не нужен. Если продуктов нет, тогда просто выведите страницу с сообщением об этом.*/

        if ($pageNum > 1){
            $query = $request->query->all();
            if($page > $pageNum){
                $this->addFlash('danger', "Invalid page number");
                return $this->redirectToRoute('product_index', ['page' => 1] + $query);
            }
            if ($limit > 100) {
                $this->addFlash('danger', "Limit exceeded");
                return $this->redirectToRoute('product_index', ['limit' => 9] + $query);
            }
        }

        return $this->render('product/products.html.twig', [
            'products' => $products,

            'currentValues' => [
                'category' => $category,
                'limit' => $limit,
                'page' => $page,
                'name' => $name,
            ],

            'totalPages' => $pageNum
        ]);
    }

    /**
     * @Route("/{productCode}", name="detroduct", requirements={"productCode":"[A][B]\d+"})
     * @param string $productCode
     * @param ProductRepository $productRepository
     * @return Response
     */

    public function getProductByCode(string $productCode, ProductRepository $productRepository): Response
    {
        $product = $productRepository->findOneBy(['code' => $productCode]);
        {
            if (!$product) {
                throw new NotFoundHttpException('Product not found.');
            }
            return $this->render('product/details.html.twig', ['product' => $product]);
        }
    }
    /**

     * @Route("/contacts", name="contacts")
     * @return Response
     */
    public function contacts(): Response
    {

        return $this->render('contacts.html.twig');

    }
    /**
     * @Route("/about", name="about")
     * @return Response
     */
    public function about(): Response
    {

        return $this->render('about.html.twig');


    }
}
