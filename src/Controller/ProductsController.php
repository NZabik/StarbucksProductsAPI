<?php

namespace App\Controller;

use App\Entity\Products;
use App\Repository\ProductsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductsController extends AbstractController
{
    #[Route('/api/products', name: 'products', methods: ['GET'])]
    public function getProducts(ProductsRepository $productsRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $products = $productsRepository->findAll();
        $jsonProducts = $serializerInterface->serialize($products, 'json');
        return new JsonResponse($jsonProducts, Response::HTTP_OK, [], true);
    }
    #[Route('/api/products/{id}', name: 'productDetail', methods: ['GET'])]
    public function getProductDetail(int $id, ProductsRepository $productsRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $product = $productsRepository->find($id);
        if ($product) {
            $jsonProduct = $serializerInterface->serialize($product, 'json');
            return new JsonResponse($jsonProduct, Response::HTTP_OK, [], true);
        } else {
            throw new NotFoundHttpException('Produit non trouvé');
        }
    }
    #[Route('/api/products/{id}', name: 'deleteProduct', methods: ['DELETE'])]
    public function deleteProduct(Products $product, EntityManagerInterface $entityManagerInterface): JsonResponse
    {
        $entityManagerInterface->remove($product);
        $entityManagerInterface->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
    #[Route('/api/products', name: 'addProduct', methods: ['POST'])]
    public function addProduct(EntityManagerInterface $entityManagerInterface, Request $request, SerializerInterface $serializerInterface, UrlGeneratorInterface $urlGeneratorInterface, ValidatorInterface $validator): JsonResponse
    {
        $product = $serializerInterface->deserialize($request->getContent(), Products::class, 'json');
        $errors = $validator->validate($product);
        if ($errors->count() > 0) {
            return new JsonResponse($serializerInterface->serialize($errors,'json'), Response::HTTP_BAD_REQUEST, [], true);
        }
        $entityManagerInterface->persist($product);
        $entityManagerInterface->flush();
        $jsonProduct = $serializerInterface->serialize($product, 'json');
        $location = $urlGeneratorInterface->generate('productDetail', ['id' => $product->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonProduct, Response::HTTP_CREATED, ['Location' => $location], true);
    }
    #[Route('/api/products/{id}', name: 'updateProduct', methods: ['PUT'])]
    public function updateProduct(Products $product, EntityManagerInterface $entityManagerInterface, Request $request, SerializerInterface $serializerInterface): JsonResponse
    {
        $product = $serializerInterface->deserialize($request->getContent(), Products::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $product]);
        $entityManagerInterface->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
    
}
