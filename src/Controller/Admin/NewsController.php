<?php

declare(strict_types=1);

namespace Manuxi\SuluNewsBundle\Controller\Admin;

use Manuxi\SuluNewsBundle\Common\DoctrineListRepresentationFactory;
use Manuxi\SuluNewsBundle\Entity\News;
use Manuxi\SuluNewsBundle\Entity\Models\NewsExcerptModel;
use Manuxi\SuluNewsBundle\Entity\Models\NewsModel;
use Manuxi\SuluNewsBundle\Entity\Models\NewsSeoModel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Manager\RouteManagerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @RouteResource("news")
 */
class NewsController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    use RequestParametersTrait;

    private NewsModel $newsModel;
    private NewsSeoModel $newsSeoModel;
    private NewsExcerptModel $newsExcerptModel;
    private DoctrineListRepresentationFactory $doctrineListRepresentationFactory;
    private RouteManagerInterface $routeManager;
    private RouteRepositoryInterface $routeRepository;
    private SecurityCheckerInterface $securityChecker;
    private TrashManagerInterface $trashManager;
    private EntityManagerInterface $entityManager;

    public function __construct(
        NewsModel $newsModel,
        NewsSeoModel $newsSeoModel,
        NewsExcerptModel $newsExcerptModel,
        RouteManagerInterface $routeManager,
        RouteRepositoryInterface $routeRepository,
        DoctrineListRepresentationFactory $doctrineListRepresentationFactory,
        SecurityCheckerInterface $securityChecker,
        ViewHandlerInterface $viewHandler,
        TrashManagerInterface $trashManager,
        EntityManagerInterface $entityManager,
        ?TokenStorageInterface $tokenStorage = null,
    ) {
        parent::__construct($viewHandler, $tokenStorage);
        $this->newsModel                        = $newsModel;
        $this->newsSeoModel                     = $newsSeoModel;
        $this->newsExcerptModel                 = $newsExcerptModel;
        $this->doctrineListRepresentationFactory = $doctrineListRepresentationFactory;
        $this->routeManager                      = $routeManager;
        $this->routeRepository                   = $routeRepository;
        $this->securityChecker                   = $securityChecker;
        $this->trashManager = $trashManager;
        $this->entityManager = $entityManager;
    }

    public function cgetAction(Request $request): Response
    {
        $locale             = $request->query->get('locale');
        $listRepresentation = $this->doctrineListRepresentationFactory->createDoctrineListRepresentation(
            News::RESOURCE_KEY,
            [],
            ['locale' => $locale]
        );

        return $this->handleView($this->view($listRepresentation));

    }

    /**
     * @param int $id
     * @param Request $request
     * @return Response
     * @throws EntityNotFoundException
     */
    public function getAction(int $id, Request $request): Response
    {
        $entity = $this->newsModel->getNews($id, $request);
        return $this->handleView($this->view($entity));
    }

    /**
     * @param Request $request
     * @return Response
     * @throws EntityNotFoundException
     */
    public function postAction(Request $request): Response
    {
        $entity = $this->newsModel->createNews($request);
        $this->updateRoutesForEntity($entity);

        return $this->handleView($this->view($entity, 201));
    }

    /**
     * @Rest\Post("/news/{id}")
     *
     * @param int $id
     * @param Request $request
     * @return Response
     * @throws MissingParameterException
     */
    public function postTriggerAction(int $id, Request $request): Response
    {
        $action = $this->getRequestParameter($request, 'action', true);

        try {
            switch ($action) {
                case 'publish':
                    $entity = $this->newsModel->publishNews($id, $request);
                    break;
                case 'draft':
                case 'unpublish':
                    $entity = $this->newsModel->unpublishNews($id, $request);
                    break;
                case 'copy':
                    $entity = $this->newsModel->copy($id, $request);
                    break;
                case 'copy-locale':
                    $locale = $this->getRequestParameter($request, 'locale', true);
                    $srcLocale = $this->getRequestParameter($request, 'src', false, $locale);
                    $destLocales = $this->getRequestParameter($request, 'dest', true);
                    $destLocales = explode(',', $destLocales);

                    foreach ($destLocales as $destLocale) {
                        $this->securityChecker->checkPermission(
                            new SecurityCondition($this->getSecurityContext(), $destLocale),
                            PermissionTypes::EDIT
                        );
                    }

                    $entity = $this->newsModel->copyLanguage($id, $request, $srcLocale, $destLocales);
                    break;
                default:
                    throw new BadRequestHttpException(sprintf('Unknown action "%s".', $action));
            }
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
            return $this->handleView($view);
        }

        return $this->handleView($this->view($entity));
    }

    public function putAction(int $id, Request $request): Response
    {
        try {
            $action = $this->getRequestParameter($request, 'action', true);
            try {
                $entity = match ($action) {
                    'publish' => $this->newsModel->publishNews($id, $request),
                    'draft', 'unpublish' => $this->newsModel->unpublishNews($id, $request),
                    default => throw new BadRequestHttpException(sprintf('Unknown action "%s".', $action)),
                };
            } catch (RestException $exc) {
                $view = $this->view($exc->toArray(), 400);
                return $this->handleView($view);
            }
        } catch(MissingParameterException $e) {
            $entity = $this->newsModel->updateNews($id, $request);
            $this->updateRoutesForEntity($entity);

            $this->newsSeoModel->updateNewsSeo($entity->getNewsSeo(), $request);
            $this->newsExcerptModel->updateNewsExcerpt($entity->getNewsExcerpt(), $request);
        }

        return $this->handleView($this->view($entity));
    }

    /**
     * @param int $id
     * @return Response
     * @throws EntityNotFoundException
     * @throws ORMException
     */
    public function deleteAction(int $id): Response
    {
        $entity = $this->newsModel->getNews($id);

        $this->trashManager->store(News::RESOURCE_KEY, $entity);

        $this->removeRoutesForEntity($entity);

        $this->newsModel->deleteNews($id, $entity->getTitle() ?? '');
        return $this->handleView($this->view(null, 204));
    }

    public function getSecurityContext(): string
    {
        return News::SECURITY_CONTEXT;
    }

    protected function updateRoutesForEntity(News $entity): void
    {
        $this->routeManager->createOrUpdateByAttributes(
            News::class,
            (string) $entity->getId(),
            $entity->getLocale(),
            $entity->getRoutePath()
        );
        // FIX zjebanego podejścia w SuluRouteBundle
        $this->entityManager->flush();

    }

    protected function removeRoutesForEntity(News $entity): void
    {
        $routes = $this->routeRepository->findAllByEntity(
            News::class,
            (string) $entity->getId(),
            $entity->getLocale()
        );

        foreach ($routes as $route) {
            $this->routeRepository->remove($route);
        }
        // FIX zjebanego podejścia w SuluRouteBundle
        $this->entityManager->flush();
    }
}
