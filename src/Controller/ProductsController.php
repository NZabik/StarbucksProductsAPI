<?php

namespace App\Controller;

use App\Entity\Products;
use App\Repository\ProductsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ProductsController extends AbstractController
{
    #[Route('/api/products', name: 'products', methods: ['GET'])]
    public function getProducts(ProductsRepository $productsRepository, SerializerInterface $serializerInterface, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);
        $idCache = "getProducts-" . $page . "-" . $limit;

        $jsonProducts = $cache->get($idCache, function (ItemInterface $item) use ($productsRepository, $page, $limit, $serializerInterface) {
            $item->tag("productsCache");
            $products = $productsRepository->findAllWithPagination($page, $limit);
            return $serializerInterface->serialize($products, 'json');
        });
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
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un produit')]
    public function deleteProduct(Products $product, EntityManagerInterface $entityManagerInterface): JsonResponse
    {
        $entityManagerInterface->remove($product);
        $entityManagerInterface->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
    #[Route('/api/products', name: 'addProduct', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour ajouter un produit')]
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
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un produit')]
    public function updateProduct(Products $product, EntityManagerInterface $entityManagerInterface, Request $request, SerializerInterface $serializerInterface): JsonResponse
    {
        $product = $serializerInterface->deserialize($request->getContent(), Products::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $product]);
        $entityManagerInterface->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
    
}
