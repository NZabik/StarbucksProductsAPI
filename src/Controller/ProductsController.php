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
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\Annotation\Groups;
use App\Service\VersioningService;
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
    public function getProducts(ProductsRepository $productsRepository, SerializerInterface $serializerInterface, Request $request, TagAwareCacheInterface $cache, VersioningService $versioningService): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);
        $idCache = "getProducts-" . $page . "-" . $limit;

        $jsonProducts = $cache->get($idCache, function (ItemInterface $item) use ($productsRepository, $page, $limit, $serializerInterface, $versioningService) {
            $version = $versioningService->getVersion();
            $context = SerializationContext::create()->setGroups(['getProducts']);
            $context->setVersion($version);
            $item->tag("productsCache");
            $item->expiresAfter(60);
            $products = $productsRepository->findAllWithPagination($page, $limit);
            return $serializerInterface->serialize($products, 'json', $context);
        });
        return new JsonResponse($jsonProducts, Response::HTTP_OK, [], true);
    }
    #[Route('/api/products/{id}', name: 'productDetail', methods: ['GET'])]
    public function getProductDetail(Products $product, SerializerInterface $serializerInterface, VersioningService $versioningService): JsonResponse
    {
        $version = $versioningService->getVersion();
        $context = SerializationContext::create()->setGroups(['getProducts']);
        $context->setVersion($version);
        $jsonBook = $serializerInterface->serialize($product, 'json', $context);
        return new JsonResponse($jsonBook, Response::HTTP_OK, ['accept' => 'json'], true);
    }
    #[Route('/api/products/{id}', name: 'deleteProduct', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un produit')]
    public function deleteProduct(Products $product, EntityManagerInterface $entityManagerInterface, TagAwareCacheInterface $cache): JsonResponse
    {
        $cache->invalidateTags(["productsCache"]);
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
        $context = SerializationContext::create()->setGroups(['getProducts']);
        $jsonProduct = $serializerInterface->serialize($product, 'json', $context);
        $location = $urlGeneratorInterface->generate('productDetail', ['id' => $product->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonProduct, Response::HTTP_CREATED, ['Location' => $location], true);
    }
    #[Route('/api/products/{id}', name: 'updateProduct', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un produit')]
    public function updateProduct(Products $currentProduct, EntityManagerInterface $entityManagerInterface, Request $request, SerializerInterface $serializerInterface, TagAwareCacheInterface $cache, ValidatorInterface $validator): JsonResponse
    {
        $product = $serializerInterface->deserialize($request->getContent(), Products::class, 'json');
        $currentProduct->setName($product->getName());
        $currentProduct->setPrice($product->getPrice());

        $errors = $validator->validate($currentProduct);
        if ($errors->count() > 0) {
            return new JsonResponse($serializerInterface->serialize($errors,'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $entityManagerInterface->persist($currentProduct);
        $entityManagerInterface->flush();

        $cache->invalidateTags(["productsCache"]);
        
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
    
}
