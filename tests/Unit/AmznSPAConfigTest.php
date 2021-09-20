<?php

namespace Jasara\AmznSPA\Tests\Unit;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Str;
use Jasara\AmznSPA\AmznSPAConfig;
use Jasara\AmznSPA\Constants\Marketplace;
use Jasara\AmznSPA\Constants\MarketplacesList;
use Jasara\AmznSPA\DTOs\ApplicationKeysDTO;
use Jasara\AmznSPA\DTOs\AuthTokensDTO;

/**
 * @covers \Jasara\AmznSPA\AmznSPAConfig
 */
class AmznSPAConfigTest extends UnitTestCase
{
    public function testGetNewConfig()
    {
        $marketplace_id = MarketplacesList::all()[rand(0, 10)]->getIdentifier();

        $application_id = $lwa_client_id = $lwa_client_secret = $aws_access_key = $aws_secret_key = '';
        $application_key_properties = ['application_id', 'lwa_client_id', 'lwa_client_secret', 'aws_access_key', 'aws_secret_key'];

        foreach ($application_key_properties as $property) {
            $$property = Str::random();
        }

        $redirect_url = Str::random();
        $lwa_refresh_token = Str::random();
        $lwa_access_token = Str::random();
        $lwa_access_token_expires_at = CarbonImmutable::now()->addSeconds(rand(100, 500));

        $config = new AmznSPAConfig(
            marketplace_id: $marketplace_id,
            application_id: $application_id,
            redirect_url: $redirect_url,
            aws_access_key: $aws_access_key,
            aws_secret_key: $aws_secret_key,
            lwa_client_id: $lwa_client_id,
            lwa_client_secret: $lwa_client_secret,
            lwa_refresh_token: $lwa_refresh_token,
            lwa_access_token: $lwa_access_token,
            lwa_access_token_expires_at: $lwa_access_token_expires_at,
        );

        $this->assertInstanceOf(Marketplace::class, $config->getMarketplace());
        $this->assertInstanceOf(Factory::class, $config->getHttp());
        $this->assertInstanceOf(AuthTokensDTO::class, $config->getTokens());
        $this->assertInstanceOf(ApplicationKeysDTO::class, $config->getApplicationKeys());

        foreach ($application_key_properties as $property) {
            $this->assertEquals($$property, $config->getApplicationKeys()->$property);
        }

        $this->assertEquals($marketplace_id, $config->getMarketplace()->getIdentifier());

        $tokens = $config->getTokens();
        $this->assertEquals($lwa_access_token, $tokens->access_token);
        $this->assertEquals($lwa_refresh_token, $tokens->refresh_token);
        $this->assertEquals($lwa_access_token_expires_at, $tokens->expires_at);

        $this->assertEquals($redirect_url, $config->getRedirectUrl());
    }

    public function testSetters()
    {
        $marketplace = MarketplacesList::all()->random(1)->first();

        $config = new AmznSPAConfig(
            marketplace_id: $marketplace->getIdentifier(),
            application_id: Str::random(),
        );

        $marketplace_2 = MarketplacesList::all()->random(1)->first();
        $config->setMarketplace($marketplace_2->getIdentifier());
        $this->assertEquals($marketplace_2->getIdentifier(), $config->getMarketplace()->getIdentifier());

        $config->setHttp(new Factory());

        $refresh_token = Str::random();
        $config->setTokens(new AuthTokensDTO(
            refresh_token: $refresh_token,
        ));

        $this->assertEquals($refresh_token, $config->getTokens()->refresh_token);
    }

    public function testIsPropertySet()
    {
        $marketplace = MarketplacesList::all()->random(1)->first();

        $config = new AmznSPAConfig(
            marketplace_id: $marketplace->getIdentifier(),
            application_id: Str::random(),
        );

        $this->assertFalse($config->isPropertySet('redirect_url'));

        $config = new AmznSPAConfig(
            marketplace_id: $marketplace->getIdentifier(),
            application_id: Str::random(),
            redirect_url: Str::random(),
        );

        $this->assertTrue($config->isPropertySet('redirect_url'));
    }
}
