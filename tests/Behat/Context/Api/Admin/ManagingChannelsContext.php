<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Sylius\PriceHistoryPlugin\Behat\Context\Api\Admin;

use ApiPlatform\Core\Api\IriConverterInterface;
use Behat\Behat\Context\Context;
use Sylius\Behat\Client\ResponseCheckerInterface;
use Sylius\Component\Core\Formatter\StringInflector;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\PriceHistoryPlugin\Domain\Model\ChannelInterface;
use Sylius1_11\Behat\Client\ApiClientInterface;
use Webmozart\Assert\Assert;

final class ManagingChannelsContext implements Context
{
    public function __construct(
        private ApiClientInterface $client,
        private ResponseCheckerInterface $responseChecker,
        private IriConverterInterface $iriConverter,
    ) {
    }

    /**
     * @When I want to modify a channel :channel
     */
    public function iWantToModifyChannel(ChannelInterface $channel): void
    {
        $this->client->buildUpdateRequest($channel->getCode());
    }

    /**
     * @When I save my changes
     * @When I try to save my changes
     */
    public function iSaveMyChanges(): void
    {
        $this->client->update();
    }

    /**
     * @Then I should be notified that it has been successfully edited
     */
    public function iShouldBeNotifiedThatItHasBeenSuccessfullyEdited(): void
    {
        Assert::true(
            $this->responseChecker->isUpdateSuccessful($this->client->getLastResponse()),
            'Channel could not be edited',
        );
    }

    /**
     * @Then I should be notified that it has been successfully created
     */
    public function iShouldBeNotifiedThatItHasBeenSuccessfullyCreated(): void
    {
        Assert::true(
            $this->responseChecker->isCreationSuccessful($this->client->getLastResponse()),
            'Channel could not be created',
        );
    }

    /**
     * @When I want to create a new channel
     */
    public function iWantToCreateANewChannel(): void
    {
        $this->client->buildCreateRequest();
    }

    /**
     * @When I specify its :field as :value
     * @When I :field it :value
     * @When I set its :field as :value
     * @When I define its :field as :value
     */
    public function iSpecifyItsAs(string $field, string $value): void
    {
        $this->client->addRequestData($field, $value);
    }

    /**
     * @When I choose :currency as the base currency
     */
    public function iChooseAsTheBaseCurrency(CurrencyInterface $currency): void
    {
        $this->client->addRequestData('baseCurrency', $this->iriConverter->getIriFromItem($currency));
    }

    /**
     * @When I make it available in :locale
     */
    public function iMakeItAvailableInLocale(LocaleInterface $locale): void
    {
        $this->client->addRequestData('locales', [$this->iriConverter->getIriFromItem($locale)]);
    }

    /**
     * @When I choose :locale as a default locale
     */
    public function iChooseAsADefaultLocale(LocaleInterface $locale): void
    {
        $this->client->addRequestData('defaultLocale', $this->iriConverter->getIriFromItem($locale));
    }

    /**
     * @When I select the :taxCalculationStrategy as tax calculation strategy
     */
    public function iSelectTaxCalculationStrategy(string $taxCalculationStrategy): void
    {
        $this->client->addRequestData('taxCalculationStrategy', StringInflector::nameToLowercaseCode($taxCalculationStrategy));
    }

    /**
     * @When I add it
     * @When I try to add it
     */
    public function iAddIt(): void
    {
        $this->client->create();
    }

    /**
     * @When /^I (enable|disable) showing the lowest price of discounted products$/
     */
    public function iEnableShowingTheLowestPriceOfDiscountedProducts(string $visible): void
    {
        $this->client->addRequestData('lowestPriceForDiscountedProductsVisible', $visible === 'enable');
    }

    /**
     * @When /^I specify (-?\d+) days as the lowest price for discounted products checking period$/
     */
    public function iSpecifyDaysAsTheLowestPriceForDiscountedProductsCheckingPeriod(int $days): void
    {
        $this->client->addRequestData('lowestPriceForDiscountedProductsCheckingPeriod', $days);
    }

    /**
     * @Then /^the ("[^"]+" channel) should have the lowest price of discounted products prior to the current discount (enabled|disabled)$/
     */
    public function theChannelShouldHaveTheLowestPriceOfDiscountedProductsPriorToTheCurrentDiscountEnabledOrDisabled(
        ChannelInterface $channel,
        string $visible,
    ): void {
        $lowestPriceForDiscountedProductsVisible = $this->responseChecker->getValue(
            $this->client->getLastResponse(),
            'lowestPriceForDiscountedProductsVisible',
        );

        Assert::same($lowestPriceForDiscountedProductsVisible, $visible === 'enabled');
    }

    /**
     * @Then /^the "[^"]+" channel should have the lowest price for discounted products checking period set to (\d+) days$/
     * @Then its lowest price for discounted products checking period should be set to :days days
     */
    public function theChannelShouldHaveTheLowestPriceForDiscountedProductsCheckingPeriodSetToDays(int $days): void
    {
        $lowestPriceForDiscountedProductsCheckingPeriod = $this->responseChecker->getValue(
            $this->client->getLastResponse(),
            'lowestPriceForDiscountedProductsCheckingPeriod',
        );

        Assert::same($lowestPriceForDiscountedProductsCheckingPeriod, $days);
    }

    /**
     * @Then I should be notified that the lowest price for discounted products checking period must be greater than 0
     */
    public function iShouldBeNotifiedThatTheLowestPriceForDiscountedProductsCheckingPeriodMustBeGreaterThanZero(): void
    {
        Assert::true($this->responseChecker->hasViolationWithMessage(
            $this->client->getLastResponse(),
            'Value must be greater than 0',
            'lowestPriceForDiscountedProductsCheckingPeriod',
        ));
    }

    /**
     * @Then I should be notified that the lowest price for discounted products checking period must be lower
     */
    public function iShouldBeNotifiedThatTheLowestPriceForDiscountedProductsCheckingPeriodMustBeLower(): void
    {
        Assert::true($this->responseChecker->hasViolationWithMessage(
            $this->client->getLastResponse(),
            'Value must be less than 2147483647',
            'lowestPriceForDiscountedProductsCheckingPeriod',
        ));
    }
}
