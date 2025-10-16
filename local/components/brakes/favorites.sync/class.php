<?php

use App\Brakes\Helper\Favorites;
use App\Brakes\Helper\FavoritesManager;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controllerable;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\SystemException;
use Bitrix\Main\Context;

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
            'update' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
            'calculate' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
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
            $options = $this->getRequestOptions();
            $items = FavoritesManager::toggleProduct($productId, $options);
            $this->refreshStateIfAuthorized();

            return $this->buildSuccessResponse($items);
        } catch (SystemException | \Throwable $exception) {
            $this->errors->setError(new Error($exception->getMessage()));
        }

        return $this->buildErrorResponse();
    }

    public function updateAction(int $productId): array
    {
        $productId = (int)$productId;
        if ($productId <= 0) {
            $this->errors->setError(new Error('Invalid product id.'));
            return $this->buildErrorResponse();
        }

        try {
            $options = $this->getRequestOptions();

            if (!FavoritesManager::updateProductOptions($productId, $options)) {
                $this->errors->setError(new Error('Product not found in favorites.'));
                return $this->buildErrorResponse();
            }

            $this->refreshStateIfAuthorized();

            return $this->buildSuccessResponse(FavoritesManager::getCurrentFavorites());
        } catch (SystemException | \Throwable $exception) {
            $this->errors->setError(new Error($exception->getMessage()));
        }

        return $this->buildErrorResponse();
    }

    public function listAction(): array
    {
        return $this->buildSuccessResponse(FavoritesManager::getCurrentFavorites());
    }

    public function calculateAction(int $productId): array
    {
        $productId = (int)$productId;
        if ($productId <= 0) {
            $this->errors->setError(new Error('Invalid product id.'));
            return $this->buildErrorResponse();
        }

        try {
            $options = $this->getRequestOptions();
            $price = FavoritesManager::getProductPrice($productId, $options);

            if ($price === null) {
                $this->errors->setError(new Error('Price calculation failed.'));
                return $this->buildErrorResponse();
            }


            return [
                'status' => 'success',
                'price' => [
                    'formatted' => $price['PRICE_FORMATTED'] ?? null,
                    'basePrice' => $price['BASE_PRICE'] ?? null,
                    'currency' => $price['CURRENCY'] ?? null,
                    'markup' => $price['MARKUP'] ?? null,
                    'raw' => $price,
                ],
            ];
        } catch (SystemException $exception) {
            $this->errors->setError(new Error($exception->getMessage()));
        } catch (\Throwable $exception) {
            $this->errors->setError(new Error($exception->getMessage()));
        }

        return $this->buildErrorResponse();
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
            'meta' => FavoritesManager::getClientState()['meta'] ?? [],
        ];
    }

    private function buildErrorResponse(): array
    {
        $errors = array_map(static fn(Error $error) => $error->getMessage(), $this->errors->toArray());

        return [
            'status' => 'error',
            'errors' => $errors,
        ];
    }

    private function getRequestOptions(): array
    {
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();
        $raw = $request->getPost('options');

        if ($raw === null) {
            $raw = $request->get('options');
        }

        if ($raw === null) {
            return [];
        }

        if (is_array($raw)) {
            return $raw;
        }

        $decoded = json_decode((string)$raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function refreshStateIfAuthorized(): void
    {
        $state = FavoritesManager::getClientState();
        if (!empty($state['isAuthorized'])) {
            FavoritesManager::refreshCurrentFavorites();
        }
    }

}
