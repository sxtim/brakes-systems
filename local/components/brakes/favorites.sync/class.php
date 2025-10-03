<?php

use App\Brakes\Helper\Favorites;
use App\Brakes\Helper\FavoritesManager;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controllerable;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\SystemException;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

if (!interface_exists(\Bitrix\Main\Engine\Controllerable::class) && interface_exists(\Bitrix\Main\Engine\Contract\Controllerable::class)) {
    class_alias(\Bitrix\Main\Engine\Contract\Controllerable::class, \Bitrix\Main\Engine\Controllerable::class);
}

class FavoritesSyncComponent extends CBitrixComponent implements Controllerable
{
    private ErrorCollection $errors;

    public function __construct($component = null)
    {
        parent::__construct($component);
        $this->errors = new ErrorCollection();
    }

    public function configureActions(): array
    {
        return [
            'toggle' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
            'list' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_GET]),
                ],
            ],
        ];
    }

    public function executeComponent(): void
    {
    }

    public function toggleAction(int $productId): array
    {
        $productId = (int)$productId;
        if ($productId <= 0) {
            $this->errors->setError(new Error('Invalid product id.'));
            return $this->buildErrorResponse();
        }

        try {
            $items = FavoritesManager::toggleProduct($productId);
            FavoritesManager::refreshCurrentFavorites();

            return $this->buildSuccessResponse($items);
        } catch (SystemException $exception) {
            $this->errors->setError(new Error($exception->getMessage()));
        } catch (\Throwable $exception) {
            $this->errors->setError(new Error($exception->getMessage()));
        }

        return $this->buildErrorResponse();
    }

    public function listAction(): array
    {
        return $this->buildSuccessResponse(FavoritesManager::getCurrentFavorites());
    }

    private function buildSuccessResponse(array $items): array
    {
        $normalized = Favorites::normalizeProductIds($items);
        $products = FavoritesManager::getFavoritesProductsData($normalized);
        $popupHtml = FavoritesManager::buildFavoritesPopupHtml($products);

        return [
            'status' => 'success',
            'items' => $normalized,
            'count' => count($normalized),
            'popupHtml' => $popupHtml,
        ];
    }

    private function buildErrorResponse(): array
    {
        return [
            'status' => 'error',
            'errors' => array_map(static fn(Error $error) => $error->getMessage(), $this->errors->toArray()),
        ];
    }
}
