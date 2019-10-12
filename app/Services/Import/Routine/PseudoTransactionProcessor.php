<?php
/**
 * PseudoTransactionProcessor.php
 * Copyright (c) 2019 - 2019 thegrumpydictator@gmail.com
 *
 * This file is part of the Firefly III CSV importer
 * (https://github.com/firefly-iii-csv-importer).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Services\Import\Routine;

use App\Services\FireflyIIIApi\Model\Account;
use App\Services\FireflyIIIApi\Model\TransactionCurrency;
use App\Services\FireflyIIIApi\Request\GetAccountRequest;
use App\Services\FireflyIIIApi\Request\GetCurrencyRequest;
use App\Services\FireflyIIIApi\Request\GetPreferenceRequest;
use App\Services\FireflyIIIApi\Response\GetAccountResponse;
use App\Services\FireflyIIIApi\Response\GetCurrencyResponse;
use App\Services\FireflyIIIApi\Response\PreferenceResponse;
use App\Services\Import\Task\AbstractTask;
use Log;

/**
 * Class PseudoTransactionProcessor
 */
class PseudoTransactionProcessor
{
    /** @var array */
    private $tasks;

    /** @var Account */
    private $defaultAccount;

    /** @var TransactionCurrency */
    private $defaultCurrency;

    /**
     * PseudoTransactionProcessor constructor.
     *
     * @param int|null $defaultAccountId
     *
     * @throws \App\Exceptions\ApiHttpException
     */
    public function __construct(?int $defaultAccountId)
    {
        $this->tasks = config('csv_importer.transaction_tasks');
        $this->getDefaultAccount($defaultAccountId);
        $this->getDefaultCurrency();
    }

    /**
     * @param array $lines
     *
     * @return array
     */
    public function processPseudo(array $lines): array
    {
        Log::debug(sprintf('Now in %s', __METHOD__));
        $processed = [];
        /** @var array $line */
        foreach ($lines as $line) {
            $processed[] = $this->processPseudoLine($line);
        }

        return $processed;

    }

    /**
     * @param int|null $accountId
     *
     * @throws \App\Exceptions\ApiHttpException
     */
    private function getDefaultAccount(?int $accountId): void
    {
        if (null !== $accountId) {
            $accountRequest = new GetAccountRequest;
            $accountRequest->setId($accountId);
            /** @var GetAccountResponse $result */
            $result               = $accountRequest->get();
            $this->defaultAccount = $result->getAccount();
        }
    }

    /**
     * @throws \App\Exceptions\ApiHttpException
     */
    private function getDefaultCurrency(): void
    {
        $prefRequest = new GetPreferenceRequest;
        $prefRequest->setName('currencyPreference');
        /** @var PreferenceResponse $response */
        $response        = $prefRequest->get();
        $code            = $response->getPreference()->data;
        $currencyRequest = new GetCurrencyRequest();
        $currencyRequest->setCode($code);
        /** @var GetCurrencyResponse $result */
        $result                = $currencyRequest->get();
        $this->defaultCurrency = $result->getCurrency();
    }

    /**
     * @param array $line
     *
     * @return array
     */
    private function processPseudoLine(array $line): array
    {
        Log::debug(sprintf('Now in %s', __METHOD__));
        foreach ($this->tasks as $task) {
            /** @var AbstractTask $object */
            $object = app($task);

            if ($object->requiresDefaultAccount()) {
                $object->setAccount($this->defaultAccount);
            }
            if ($object->requiresTransactionCurrency()) {
                $object->setTransactionCurrency($this->defaultCurrency);
            }

            $line = $object->process($line);
        }
        var_dump($line);
        exit;

        return $line;
    }

}