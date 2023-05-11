<?php

declare(strict_types=1);

/*
 * This file is part of Bilemo
 *
 * (c)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Consumer;
use App\Repository\ConsumerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Hateoas\Representation\CollectionRepresentation;
use Hateoas\Representation\PaginatedRepresentation;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\CacheService;

class ConsumerController extends AbstractController
{
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private CacheService $cacheService;

    public function __construct(SerializerInterface $serializer, ValidatorInterface $validator, CacheService $cacheService)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->cacheService = $cacheService;
    }

    /**
     * List of consumers function and pagination système.
     */
    #[Route('api/consumers', name: 'app_allConsumers', methods: ['GET'])]
    public function getConsumers(Request $request, ConsumerRepository $repoConsumer): JsonResponse
    {
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('limit', 3);

        // Cache 
        $idCache = $this->cacheService->idCacheCreation([CONSUMER::CACHECONSUMER, $page, $limit]);
        $listConsumer = $this->cacheService->cachePoolCreation($idCache, $repoConsumer, CONSUMER::CACHECONSUMER, $this->getUser());
       

        // Set offset/position for slice function in array listConsumer
        $offset = ($page - 1) * $limit;

        // Create CollectionRepresentation for pagination HateOAS function
        $listConsumerShorted = new CollectionRepresentation(\array_slice($listConsumer, $offset, $limit));

        // Set and cast to int the number of pages.
        $nbPages = (int) ceil(\count($listConsumer) / $limit);

        // Create pagination with HateOAS
        $paginatedCollection = new PaginatedRepresentation(
            $listConsumerShorted,
            'app_allConsumers', // route
            [], // route parameters
            $page,       // page number
            $limit,      // limit
            $nbPages,       // total pages
            'page',  // page route parameter name, optional, defaults to 'page'
            'limit', // limit route parameter name, optional, defaults to 'limit'
            false,   // generate relative URIs, optional, defaults to `false`
            \count($listConsumer)       // total collection size, optional, defaults to `null`
        );

        $jsonConsumerList = $this->serializer->serialize($paginatedCollection, 'json');

        return new JsonResponse($jsonConsumerList, Response::HTTP_OK, [], true);
    }

    /**
     * Get consumer function.
     */
    #[Route('api/consumers/{id}', name: 'app_detailConsumer', methods: ['GET'])]
    #[Security("is_granted('VIEW', consumer)", statusCode: 403, message: 'Forbidden-Resource not found.')]
    public function getDetailConsumer(Consumer $consumer): JsonResponse
    {
        // var getConsumers represent a group in class Consumer to get specify informations and cancel circule errors
        $context = SerializationContext::create()->setGroups(['getConsumers']);
        $jsonConsumer = $this->serializer->serialize($consumer, 'json', $context);

        return new JsonResponse($jsonConsumer, Response::HTTP_OK, [], true);
    }

    /**
     * Update consumer function.
     */
    #[Route('api/consumers/{id}', name: 'app_updateConsumer', methods: ['PUT'])]
    #[Security("is_granted('EDIT', currentConsumer)", statusCode: 403, message: 'Forbidden-Resource not found.')]
    public function updateConsumer(Consumer $currentConsumer,Request $request,EntityManagerInterface $entityManager): JsonResponse
    {
        // Clear cache to refresh data about this modification
        $this->cacheService->idDeleting(['consumersCache']);
        
        // Create newConsummer with new values
        $newConsumer = $this->serializer->deserialize($request->getContent(), Consumer::class, 'json');

        // Modify currentConsumer with new values from newConsummer
        $currentConsumer->setLastname($newConsumer->getLastname())
                        ->setFirstname($newConsumer->getFirstname());

        // Checking errors
        $errors = $this->validator->validate($currentConsumer);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // EntityManager
        $entityManager->persist($currentConsumer);
        $entityManager->flush();

        // Create a group to cancel circule errors and get User in Consumer
        $context = SerializationContext::create()->setGroups(['getConsumers']);
        $jsonConsumer = $this->serializer->serialize($currentConsumer, 'json', $context);

        return new JsonResponse($jsonConsumer, Response::HTTP_OK, [], true);
    }

    /**
     * Delete consumer function.
     */
    #[Route('api/consumers/{id}', name: 'app_deleteConsumer', methods: ['DELETE'])]
    #[Security("is_granted('DELETE', consumer)", statusCode: 403, message: 'Forbidden-Resource not found.')]
    public function deleteConsumer(Consumer $consumer, EntityManagerInterface $entityManager): JsonResponse
    {
        // Clear cache to refresh data about this modification
        $this->cacheService->idDeleting(['consumersCache']);
        // EntityManager
        $entityManager->remove($consumer);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Creation consumer function.
     */
    #[Route('api/consumers', name: 'app_creationConsumer', methods: ['POST'])]
    public function creationConsumer(
        Request $request,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        // Deserialize infos from the request, create Consumer and setUser is logged User
        $consumer = $this->serializer->deserialize($request->getContent(), Consumer::class, 'json');
        $user = $this->getUser();
        $consumer->setUser($user);

        // Checking errors
        $errors = $this->validator->validate($consumer);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // EntityManager
        $entityManager->persist($consumer);
        $entityManager->flush();

        // Create a group to cancel circule errors and get User in Consumer
        $context = SerializationContext::create()->setGroups(['getConsumers']);
        $jsonConsumer = $this->serializer->serialize($consumer, 'json', $context);

        // Create link with the new Consumer
        $location = $urlGenerator->generate('app_detailConsumer', ['id' => $consumer->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonConsumer, Response::HTTP_CREATED, ['Location' => $location], true);
    }

}
