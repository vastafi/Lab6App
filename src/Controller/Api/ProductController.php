<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use App\Response\ApiErrorResponse;

/**
 * @Route("/api/v1/products")
 */

class ProductController extends AbstractController
{
    /**
     * @Route("/", name="search", methods={"GET"})
     */
    public function index(Request $request, ProductRepository $repo): Response
    {
        $category = $request->query->get('category', null);
        $name = $request->query->get('name', null);
        $limit = $request->query->get('limit', 8);
        $page = $request->query->get('page', 1);
        if($limit > 100){
            return new ApiErrorResponse('1226', 'Search limit cannot exceed 100 items.');
        }
        if($limit <= 0){
            return new ApiErrorResponse('1624', 'Search limit cannot be negative or zero.');
        }
        if($page <= 0){
            return new ApiErrorResponse('1625', 'Page cannot be negative or zero.');
        }
        $totalPages = $repo->countPages($category, $name, $limit);
        if($page > $totalPages){
            return new ApiErrorResponse('1630', 'This page number does not exist.');
        }
        return $this->json(["items"=>$repo->filter($category,
            $name,
            $limit,
            $page), "pagination"=>["limit"=>$limit, "page"=>$page]]);
    }

    /**
     * @Route("/{productCode}", name="api.product.details",requirements={"productCode":"[A][B]\d+"}, methods={"GET"})
     */
    public function getProductByCode(string $productCode, ProductRepository $repo): Response
    {
        $repo=$this->getDoctrine()->getRepository(Product::class);
        $product = $repo->findOneBy(['code'=>$productCode]);
        if(!$product){
            return new Response(null, 404);
        }
        return $this->json($product);
    }

    /**
     * @Route ("/", name="create_prod_api",methods={"POST"})
     */
    public function createProduct(Request $request, EntityManagerInterface $em, ProductRepository $repo): Response
    {
        $content = $request->toArray();

        if(isset($content['code']) !== true ){
            return new ApiErrorResponse(400,'Code cant be null!');
        }
        elseif (strlen($content['code']) == 0 ){
            return new ApiErrorResponse(400,'Code cant be blank!');
        }
        elseif(isset($content['name'])==false){
            return new ApiErrorResponse(400,'Name cant be null!');
        }
        elseif (strlen($content['name']) == 0){
            return new ApiErrorResponse(400,'Name cant be blank!');
        }
        elseif(isset($content['price'])==false){
            return new ApiErrorResponse(400,'Price cant be null!');
        }
        elseif (strlen($content['price']) == 0){
            return new ApiErrorResponse(400,'Price cant be blank!');
        }
        elseif(isset($content['category'])==false){
            return new ApiErrorResponse(400,'Category cant be null!');
        }
        elseif (strlen($content['category']) == 0){
            return new ApiErrorResponse(400,'Category cant be blank!');
        }
        elseif(isset($content['availableAmount'])==false){
            return new ApiErrorResponse(400,'Available amount cant be null!');
        }
        elseif (strlen($content['availableAmount']) == 0){
            return new ApiErrorResponse(400,'Available amount cant be blank!');
        }
        elseif($content['availableAmount']< 0){
            return new ApiErrorResponse(400,'Available amount cant negative!');
        }
        elseif ($content['price'] < 0){
            return new ApiErrorResponse(400,'Price cant be negative');
        }

        $product = new Product();
        $product->setCode($content['code']);
        $product->setName($content['name']);
        $product->setCategory($content['category']);
        $product->setPrice($content['price']);
        $product->setDescription($content['description']);
        $product->setCreatedAt(new DateTime(null, new DateTimeZone('Europe/Athens')));
        $product->setAvailableAmount($content['availableAmount']);

        if ($repo->count(['code'=> $content['code']]) > 0){
            return new ApiErrorResponse(400,'A product with this code exists already!');
        }

        $em->persist($product);

        $em->flush();

        return new Response(null,201);
    }

    /**
     * @Route ("/{productCode}", name="update", requirements={"productCode":"[A][B]\d+"}, methods={"PUT"})
     */
    public function updateProduct(string $productCode, Request $request, ProductRepository $repo): Response
    {
        $data = json_decode($request->getContent(), true);
        $product = $repo->findOneBy(['code' => $productCode]);
        if(!$product){
            return new Response(null, 404);
        }
        $product->setUpdatedAt(new DateTime(null, new DateTimeZone('Europe/Athens')));
        $product->setCode($productCode);
        $form = $this->createForm(ProductType::class, $product);
        $form->remove('code');
        $fields = ['name', 'category', 'price', 'description', 'availableAmount'];
        foreach ($fields as $field){
            if(!isset($data[$field]) || strlen($data[$field]) === 0){
                return new ApiErrorResponse('1256', $field." can not be null");
            }
        }
        if($data['price'] <= 0){
            return new ApiErrorResponse('1279', 'price can not be null or 0');
        }
        if($data['availableAmount'] < 0){
            return new ApiErrorResponse('1289', 'available amount can not be negative');
        }
        $form->submit($data);
        $em = $this->getDoctrine()->getManager();
        $em->persist($product);
        $em->flush();
        return new Response(null, 200);
    }
    /**
     * @Route("/{productCode}", name="delete_product_api",requirements={"productCode":"[A][B]\d+"}, methods={"DELETE"})
     */
    public function deleteProductByCode(string $productCode, EntityManagerInterface $entityManager, ProductRepository $repo):Response
    {
        $product = $repo->findOneBy(['code' => $productCode]);
        if(!$product){
            return new Response(null, 404);
        }

        $entityManager->remove($product);
        $entityManager->flush();

        return new Response();
    }
}
