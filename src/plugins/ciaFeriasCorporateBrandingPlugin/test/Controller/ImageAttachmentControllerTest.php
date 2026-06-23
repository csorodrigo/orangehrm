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

namespace CiaFerias\Tests\CorporateBranding\Controller;

use CiaFerias\Config\Config;
use CiaFerias\CorporateBranding\Controller\File\ImageAttachmentController;
use CiaFerias\CorporateBranding\Service\ThemeService;
use CiaFerias\Framework\Services;
use CiaFerias\Tests\Util\KernelTestCase;
use CiaFerias\Tests\Util\TestDataService;

/**
 * @group CorporateBranding
 * @group Controller
 */
class ImageAttachmentControllerTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        $fixture = Config::get(Config::PLUGINS_DIR)
            . '/ciaFeriasCorporateBrandingPlugin/test/fixtures/Theme.yaml';
        TestDataService::populate($fixture);
    }

    public function testClientLogo(): void
    {
        $this->createKernelWithMockServices([
            Services::THEME_SERVICE => new ThemeService(),
        ]);
        $controller = new ImageAttachmentController();
        $request = $this->getHttpRequest([], [], ['imageName' => 'clientLogo']);
        $response = $controller->handle($request);
        $this->assertEquals('image/png', $response->headers->get('content-type'));
        $this->assertEquals('2431', $response->headers->get('content-length'));
        $this->assertEquals(
            "attachment; filename*=utf-8''cia-ferias-logo.png",
            $response->headers->get('content-disposition')
        );
        $this->assertEquals(
            'max-age=0, must-revalidate, post-check=0, pre-check=0, public',
            $response->headers->get('cache-control')
        );
        $this->assertEquals('binary', $response->headers->get('content-transfer-encoding'));
        $this->assertEquals('Public', $response->headers->get('pragma'));
        $this->assertEquals(
            2431,
            mb_strlen(hex2bin(substr($response->getContent(), 2, strlen($response->getContent()))), '8bit')
        );
    }

    public function testClientBanner(): void
    {
        $this->createKernelWithMockServices([
            Services::THEME_SERVICE => new ThemeService(),
        ]);
        $controller = new ImageAttachmentController();
        $request = $this->getHttpRequest([], [], ['imageName' => 'clientBanner']);
        $response = $controller->handle($request);
        $this->assertEquals('image/png', $response->headers->get('content-type'));
        $this->assertEquals('2680', $response->headers->get('content-length'));
        $this->assertEquals(
            "attachment; filename*=utf-8''cia-ferias-banner.png",
            $response->headers->get('content-disposition')
        );
        $this->assertEquals(
            'max-age=0, must-revalidate, post-check=0, pre-check=0, public',
            $response->headers->get('cache-control')
        );
        $this->assertEquals('binary', $response->headers->get('content-transfer-encoding'));
        $this->assertEquals('Public', $response->headers->get('pragma'));
        $this->assertEquals(
            2680,
            mb_strlen(hex2bin(substr($response->getContent(), 2, strlen($response->getContent()))), '8bit')
        );
    }

    public function testLoginBanner(): void
    {
        $this->createKernelWithMockServices([
            Services::THEME_SERVICE => new ThemeService(),
        ]);
        $controller = new ImageAttachmentController();
        $request = $this->getHttpRequest([], [], ['imageName' => 'loginBanner']);
        $response = $controller->handle($request);
        $this->assertEquals('image/png', $response->headers->get('content-type'));
        $this->assertEquals('21848', $response->headers->get('content-length'));
        $this->assertEquals(
            "attachment; filename*=utf-8''cia-ferias-brand.png",
            $response->headers->get('content-disposition')
        );
        $this->assertEquals(
            'max-age=0, must-revalidate, post-check=0, pre-check=0, public',
            $response->headers->get('cache-control')
        );
        $this->assertEquals('binary', $response->headers->get('content-transfer-encoding'));
        $this->assertEquals('Public', $response->headers->get('pragma'));
        $this->assertEquals(
            21848,
            mb_strlen(hex2bin(substr($response->getContent(), 2, strlen($response->getContent()))), '8bit')
        );
    }

    public function testInvalidImageName(): void
    {
        $controller = new ImageAttachmentController();
        $request = $this->getHttpRequest([], [], ['imageName' => 'invalid']);
        $response = $controller->handle($request);
        $this->assertEquals(400, $response->getStatusCode());
    }
}
