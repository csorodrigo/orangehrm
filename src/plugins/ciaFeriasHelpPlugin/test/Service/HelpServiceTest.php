<?php

/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with OrangeHRM.
 * If not, see <https://www.gnu.org/licenses/>.
 */

namespace CiaFerias\Tests\Help\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use CiaFerias\Config\Config;
use CiaFerias\Help\Processor\ZendeskHelpProcessor;
use CiaFerias\Help\Service\HelpConfigService;
use CiaFerias\Help\Service\HelpService;
use CiaFerias\Tests\Util\KernelTestCase;
use CiaFerias\Tests\Util\TestDataService;

/**
 * @group Help
 * @group Service
 */
class HelpServiceTest extends KernelTestCase
{
    private HelpService $helpService;
    protected string $fixture;

    protected function setUp(): void
    {
        $this->helpService = new HelpService();
        $this->fixture = Config::get(
            Config::PLUGINS_DIR
        ) . '/ciaFeriasHelpPlugin/test/fixtures/HelpServiceTest.yaml';
        TestDataService::populate($this->fixture);
    }

    public function testGetHelpConfigService(): void
    {
        $helpConfigService = $this->helpService->getHelpConfigService();
        $this->assertInstanceOf(HelpConfigService::class, $helpConfigService);
    }

    public function testGetZendeskHelpProcessor(): void
    {
        $helpProcessor = $this->helpService->getHelpProcessor();
        $this->assertInstanceOf(ZendeskHelpProcessor::class, $helpProcessor);
    }

    public function testGetRedirectUrl(): void
    {
        $zendeskHelpProcessorMock = $this->getMockBuilder(ZendeskHelpProcessor::class)
            ->onlyMethods(['getHttpClient'])
            ->getMock();

        $mockHandler = new MockHandler([
            new Response(
                200,
                [],
                '{"count":1,"next_page":null,"page":1,"page_count":1,"per_page":25,"previous_page":null,"results":[{"id":360018588480,"html_url":"https://help.cia-ferias.local/hc/pt-br/articles/360018588480-Como-adicionar-um-usuario","name":"Como adicionar um usuario","title":"Como adicionar um usuario","source_locale":"pt-br","locale":"pt-br","outdated":false,"outdated_locales":[],"label_names":["admin_viewSystemUsers","admin_saveSystemUser"],"snippet":"Como criar uma conta de usuario para os fluxos internos da CIA Ferias","result_type":"article"}]}'
            )
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handlerStack]);

        $zendeskHelpProcessorMock->expects($this->once())
            ->method('getHttpClient')
            ->willReturn($client);

        $helpServiceMock = $this->getMockBuilder(HelpService::class)
            ->onlyMethods(['getHelpProcessor'])
            ->getMock();

        $helpServiceMock->expects($this->once())
            ->method('getHelpProcessor')
            ->willReturn($zendeskHelpProcessorMock);

        $redirectUrl = $helpServiceMock->getRedirectUrl('admin_viewSystemUsers');
        $this->assertEquals(
            "https://help.cia-ferias.local/hc/pt-br/articles/360018588480-Como-adicionar-um-usuario",
            $redirectUrl
        );
    }

    public function testGetRedirectUrl2(): void
    {
        $zendeskHelpProcessorMock = $this->getMockBuilder(ZendeskHelpProcessor::class)
            ->onlyMethods(['getHttpClient'])
            ->getMock();

        $mockHandler = new MockHandler([
            new Response(
                200,
                [],
                '{"count":0,"next_page":null,"page":1,"page_count":0,"per_page":25,"previous_page":null,"results":[]}'
            )
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handlerStack]);

        $zendeskHelpProcessorMock->expects($this->once())
            ->method('getHttpClient')
            ->willReturn($client);

        $helpServiceMock = $this->getMockBuilder(HelpService::class)
            ->onlyMethods(['getHelpProcessor'])
            ->getMock();

        $helpServiceMock->expects($this->once())
            ->method('getHelpProcessor')
            ->willReturn($zendeskHelpProcessorMock);

        $redirectUrl = $helpServiceMock->getRedirectUrl('admin_viewSystemUsers');
        $this->assertEquals(
            "/hc/en-us",
            $redirectUrl
        );
    }

    public function testGetDefaultRedirectUrl(): void
    {
        $defaultRedirectUrl = $this->helpService->getDefaultRedirectUrl();
        $this->assertEquals(
            "/hc/en-us",
            $defaultRedirectUrl
        );
    }

    public function testIsValidUrl(): void
    {
        $valid = $this->helpService->isValidUrl();
        $this->assertFalse($valid);
    }

    public function testIsValidUrl2(): void
    {
        $helpConfigServiceMock = $this->getMockBuilder(HelpConfigService::class)
            ->onlyMethods(['getBaseHelpUrl'])
            ->getMock();

        $helpConfigServiceMock->expects($this->once())
            ->method('getBaseHelpUrl')
            ->willReturn('abcdefg');

        $helpServiceMock = $this->getMockBuilder(HelpService::class)
            ->onlyMethods(['getHelpConfigService'])
            ->disableOriginalConstructor()
            ->getMock();

        $helpServiceMock->expects($this->once())
            ->method('getHelpConfigService')
            ->willReturn($helpConfigServiceMock);

        $valid = $helpServiceMock->isValidUrl();
        $this->assertFalse($valid);
    }
}
