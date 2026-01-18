<?php

use App\Brakes\Helper\BasketManager;
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

class BasketActionsComponent extends CBitrixComponent implements Controllerable
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
            'add' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
            'update' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
            'remove' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
            'summary' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_GET]),
                ],
            ],
        ];
    }

    public function executeComponent(): void
    {
    }

    public function addAction(int $productId, float $quantity = 1): array
    {
        $productId = (int)$productId;
        $quantity = (float)$quantity;
        if ($productId <= 0 || $quantity <= 0) {
            $this->errors->setError(new Error('Invalid product or quantity.'));
            return $this->buildErrorResponse();
        }

        try {
            $context = $this->getRequestArray('context');
            $options = $this->getRequestArray('options');
            $summary = BasketManager::addProduct($productId, $quantity, $context, $options);

            return [
                'status' => 'success',
                'summary' => $summary,
            ];
        } catch (SystemException | \Throwable $exception) {
            $this->errors->setError(new Error($exception->getMessage()));
        }

        return $this->buildErrorResponse();
    }

    public function updateAction(int $basketItemId, float $quantity): array
    {
        $basketItemId = (int)$basketItemId;
        $quantity = (float)$quantity;
        if ($basketItemId <= 0 || $quantity <= 0) {
            $this->errors->setError(new Error('Invalid basket item or quantity.'));
            return $this->buildErrorResponse();
        }

        try {
            $summary = BasketManager::updateQuantity($basketItemId, $quantity);
            return [
                'status' => 'success',
                'summary' => $summary,
            ];
        } catch (SystemException | \Throwable $exception) {
            $this->errors->setError(new Error($exception->getMessage()));
        }

        return $this->buildErrorResponse();
    }

    public function removeAction(int $basketItemId): array
    {
        $basketItemId = (int)$basketItemId;
        if ($basketItemId <= 0) {
            $this->errors->setError(new Error('Invalid basket item id.'));
            return $this->buildErrorResponse();
        }

        try {
            $summary = BasketManager::removeItem($basketItemId);
            return [
                'status' => 'success',
                'summary' => $summary,
            ];
        } catch (SystemException | \Throwable $exception) {
            $this->errors->setError(new Error($exception->getMessage()));
        }

        return $this->buildErrorResponse();
    }

    public function summaryAction(): array
    {
        return [
            'status' => 'success',
            'summary' => BasketManager::getSummary(),
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

    private function getRequestArray(string $key): array
    {
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();
        $raw = $request->getPost($key);
        if ($raw === null) {
            $raw = $request->get($key);
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
}
