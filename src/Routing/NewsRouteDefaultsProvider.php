<?php

declare(strict_types=1);

namespace Manuxi\SuluNewsBundle\Routing;

use Manuxi\SuluNewsBundle\Controller\Website\NewsController;
use Manuxi\SuluNewsBundle\Entity\News;
use Manuxi\SuluNewsBundle\Repository\NewsRepository;
use Sulu\Bundle\RouteBundle\Routing\Defaults\RouteDefaultsProviderInterface;

class NewsRouteDefaultsProvider implements RouteDefaultsProviderInterface
{

    private NewsRepository $repository;

    public function __construct(NewsRepository $repository) {
        $this->repository = $repository;
    }

    /**
     * @param $entityClass
     * @param $id
     * @param $locale
     * @param null $object
     * @return mixed[]
     */
    public function getByEntity($entityClass, $id, $locale, $object = null)
    {
        return [
            '_controller' => NewsController::class . '::indexAction',
            //'news' => $object ?: $this->repository->findById((int)$id, $locale),
            'news' => $this->repository->findById((int)$id, $locale),
        ];
    }

    public function isPublished($entityClass, $id, $locale): bool
    {
        $news = $this->repository->findById((int)$id, $locale);
        if (!$this->supports($entityClass) || !$news instanceof News) {
            return false;
        }
        return $news->isPublished();
    }

    public function supports($entityClass)
    {
        return News::class === $entityClass;
    }
}
