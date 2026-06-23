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

namespace CiaFerias\Tests\Core\Service;

use CiaFerias\Admin\Dao\EmailConfigurationDao;
use CiaFerias\Admin\Service\EmailConfigurationService;
use CiaFerias\Core\Service\ConfigService;
use CiaFerias\Core\Service\EmailService;
use CiaFerias\Entity\EmailConfiguration;
use CiaFerias\Framework\Logger\Logger;
use CiaFerias\Core\Utility\Mailer;
use CiaFerias\Core\Utility\MailMessage;
use CiaFerias\Core\Utility\MailTransport;
use CiaFerias\Framework\Services;
use CiaFerias\Tests\Util\KernelTestCase;

/**
 * @group Admin
 * @group Service
 */
class EmailServiceTest extends KernelTestCase
{
    private EmailConfigurationService $emailConfigurationService;
    private EmailService $emailService;

    protected function setUp(): void
    {
        $this->emailConfigurationService = new EmailConfigurationService();
    }

    public function testGetEmailConfigurationDao()
    {
        $this->assertTrue(
            $this->emailConfigurationService->getEmailConfigurationDao() instanceof EmailConfigurationDao
        );
    }

    public function testSendTestMail(): void
    {
        $emailService = $this->getMockBuilder(EmailService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['sendTestEmail'])
            ->getMock();

        $emailService->expects($this->once())
            ->method('sendTestEmail')
            ->with('test1@ciaferias.local')
            ->willReturn(true);

        $this->emailConfigurationService->setEmailService($emailService);
        $result = $this->emailConfigurationService->sendTestMail('test1@ciaferias.local');
        $this->assertEquals(true, $result);
    }

    public function xtestGetConfigService()
    {
        // TODO
        $this->emailService = $this->getMockBuilder(EmailService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $this->assertTrue($this->emailService->getConfigService() instanceof ConfigService);
    }

    public function xtestLoadConfiguration()
    {
        // TODO
        $emailConfiguration = new EmailConfiguration();
        $emailConfiguration->setId(1);
        $emailConfiguration->setMailType("smtp");
        $emailConfiguration->setSentAs("test@cia-ferias.local");
        $emailConfiguration->setSmtpHost("smtp.gmail.com");
        $emailConfiguration->setSmtpPort(587);
        $emailConfiguration->setSmtpUsername("testUN");
        $emailConfiguration->setSmtpPassword("testPW");
        $emailConfiguration->setSmtpAuthType("login");
        $emailConfiguration->setSmtpSecurityType("tls");

        $emailConfigDao = $this->getMockBuilder(EmailConfigurationDao::class)
            ->onlyMethods(['getEmailConfiguration'])
            ->getMock();

        $emailConfigDao->expects($this->once())
            ->method('getEmailConfiguration')
            ->willReturn($emailConfiguration);

        $emailConfigService = $this->getMockBuilder(EmailConfigurationService::class)
            ->onlyMethods(['getEmailConfigurationDao'])
            ->getMock();

        $emailConfigService->expects($this->once())
            ->method('getEmailConfigurationDao')
            ->willReturn($emailConfigDao);

        $emailService = $this->getMockBuilder(EmailService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLogger'])
            ->getMock();

        $emailService->expects($this->once())
            ->method('getLogger')
            ->willReturn(new Logger('email'));

        $emailService->setEmailConfigurationService($emailConfigService);

        $configService = $this->getMockBuilder(ConfigService::class)
            ->onlyMethods(['getSendmailPath'])
            ->getMock();

        $configService->expects($this->once())
            ->method('getSendmailPath')
            ->willReturn('test path');

        $this->createKernelWithMockServices([Services::CONFIG_SERVICE => $configService]);
        $this->assertEquals('smtp', $emailService->getEmailConfig()->getMailType());
        $this->assertEquals('test@cia-ferias.local', $emailService->getEmailConfig()->getSentAs());
        $this->assertEquals('test path', $emailService->getSendmailPath());
    }

    public function testGetMailer()
    {
        $transport = new MailTransport('smtp.gmail.com', 587);

        $emailService = $this->getMockBuilder(EmailService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTransport'])
            ->getMock();

        $emailService->expects($this->once())
            ->method('getTransport')
            ->willReturn($transport);
        $this->assertTrue($emailService->getMailer() instanceof Mailer);
    }

    public function testGetTransport()
    {
        $emailConfiguration = new EmailConfiguration();
        $emailConfiguration->setId(1);
        $emailConfiguration->setMailType("smtp");
        $emailConfiguration->setSentAs("test@cia-ferias.local");
        $emailConfiguration->setSmtpHost("smtp.gmail.com");
        $emailConfiguration->setSmtpPort(587);
        $emailConfiguration->setSmtpUsername("testUN");
        $emailConfiguration->setSmtpPassword("testPW");
        $emailConfiguration->setSmtpAuthType("login");
        $emailConfiguration->setSmtpSecurityType("tls");

        $this->emailService = $this->getMockBuilder(EmailService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $this->emailService->setEmailConfig($emailConfiguration);
        $this->emailService->setConfigSet(true);
        $this->assertTrue($this->emailService->getTransport() instanceof MailTransport);
    }

    public function testGetMessage()
    {
        $this->emailService = $this->getMockBuilder(EmailService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->emailService->setMessageSubject('test subject');
        $this->emailService->setMessageFrom(['testFrom@ciaferias.local' => 'cia-ferias']);
        $this->emailService->setMessageTo(['testTo@ciaferias.local']);
        $this->emailService->setMessageBody('test body');
        $this->emailService->setMessageCc(['testCc@ciaferias.local']);
        $this->emailService->setMessageBcc(['testBcc@ciaferias.local']);
        $this->assertTrue($this->emailService->getMessage() instanceof MailMessage);
    }

    public function testReadFileLoadsValidEmailTemplate(): void
    {
        $this->createKernel();
        $service = new EmailServiceReadFileTestProxy();
        $path = '/ciaFeriasLeavePlugin/Mail/templates/en_US/apply/leaveApplicationSubject.txt.twig';
        $content = $service->readFileExposed($path);
        $this->assertStringContainsString('Leave Notification', $content);

        $path = 'ciaFeriasLeavePlugin/Mail/templates/en_US/apply/leaveApplicationSubject.txt.twig';
        $content = $service->readFileExposed($path);
        $this->assertStringContainsString('Leave Notification', $content);
    }

    public function testReadNulbyteInPathEmailTemplate(): void
    {
        $this->createKernel();
        $service = new EmailServiceReadFileTestProxy();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File is not readable');
        $path = '/ciaFeriasLeavePlugin/Mail/templates/en_US/apply/leaveApplicationSubject.txt.twig\0';
        $content = $service->readFileExposed($path);
    }

    public function testReadInvalidPathEmailTemplate(): void
    {
        $this->createKernel();
        $service = new EmailServiceReadFileTestProxy();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File is not readable');
        $path = '/ciaFeriasLeavePlugin/Mail/templates/en_US/apply/leaveApplicationSubject.txt';
        $content = $service->readFileExposed($path);
    }

    public function testReadFileRejectsPathTraversal(): void
    {
        $this->createKernel();
        $service = new EmailServiceReadFileTestProxy();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File is not readable');
        $service->readFileExposed('/../../../../../../etc/hosts');
    }

    public function testReadFileRejectsResolvedPathOutsidePluginsDirectory(): void
    {
        $this->createKernel();
        $service = new EmailServiceReadFileTestProxy();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File is not readable');
        $traversal = str_repeat('..' . DIRECTORY_SEPARATOR, 12) . 'etc' . DIRECTORY_SEPARATOR . 'hosts';
        $service->readFileExposed($traversal);
    }
}

/**
 * Exposes protected readFile for security regression tests.
 *
 * @internal
 */
final class EmailServiceReadFileTestProxy extends EmailService
{
    public function __construct()
    {
    }

    public function readFileExposed(string $path): string
    {
        return $this->readFile($path);
    }
}
