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

use CiaFerias\Config\Config;
use CiaFerias\Core\Dao\EmailQueueDao;
use CiaFerias\Core\Service\DateTimeHelperService;
use CiaFerias\Core\Service\EmailQueueService;
use CiaFerias\Core\Service\EmailService;
use CiaFerias\Entity\Mail;
use CiaFerias\Framework\Services;
use CiaFerias\Tests\Util\KernelTestCase;
use CiaFerias\Tests\Util\TestDataService;

/**
 * @group Core
 * @group Service
 */
class EmailQueueServiceTest extends KernelTestCase
{
    private EmailQueueService $emailQueueService;

    protected function setUp(): void
    {
        $this->emailQueueService = new EmailQueueService();
        $fixture = Config::get(Config::PLUGINS_DIR) .
            '/ciaFeriasCorePlugin/test/fixtures/EmailQueueDao.yml';
        TestDataService::populate($fixture);
    }

    public function testGetEmailQueueDao()
    {
        $this->assertTrue($this->emailQueueService->getEmailQueueDao() instanceof EmailQueueDao);
    }

    public function testGetEmailService()
    {
        $emailQueueService = $this->getMockBuilder(EmailQueueService::class)
            ->onlyMethods(['getEmailService'])
            ->getMock();
        $emailQueueService->expects($this->once())
            ->method('getEmailService');
        $emailQueueService->getEmailService();
    }

    public function testAddToQueue()
    {
        $this->createKernelWithMockServices([Services::DATETIME_HELPER_SERVICE => new DateTimeHelperService()]);

        $result = $this->emailQueueService->addToQueue(
            'test7 subject',
            'test7 body',
            ['test7@ciaferias.local', 'test8@ciaferias.local'],
            Mail::CONTENT_TYPE_TEXT_PLAIN,
            ['test9@ciaferias.local'],
            ['test10@ciaferias.local']
        );
        $this->assertTrue($result instanceof Mail);
        $this->assertEquals('test7 subject', $result->getSubject());
        $this->assertEquals('test7 body', $result->getBody());
        $this->assertEquals(['test7@ciaferias.local', 'test8@ciaferias.local'], $result->getToList());
        $this->assertEquals(['test9@ciaferias.local'], $result->getCcList());
        $this->assertEquals(['test10@ciaferias.local'], $result->getBccList());
    }

    public function testSendSingleMail()
    {
        $emailService = $this->getMockBuilder(EmailService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                ['setMessageSubject', 'setMessageBody', 'setMessageTo', 'setMessageCc', 'setMessageBcc', 'sendEmail']
            )
            ->getMock();
        $emailService->expects($this->once())
            ->method('setMessageSubject');
        $emailService->expects($this->once())
            ->method('setMessageBody');
        $emailService->expects($this->once())
            ->method('setMessageTo');
        $emailService->expects($this->once())
            ->method('setMessageCc');
        $emailService->expects($this->once())
            ->method('setMessageBcc');
        $emailService->expects($this->once())
            ->method('sendEmail');
        $emailQueueService = $this->getMockBuilder(EmailQueueService::class)
            ->onlyMethods(['getEmailService'])
            ->getMock();
        $emailQueueService->expects($this->exactly(6))
            ->method('getEmailService')
            ->willReturn($emailService);
        $this->createKernelWithMockServices([Services::DATETIME_HELPER_SERVICE => new DateTimeHelperService()]);

        $emailQueueService->sendSingleMail(1);
    }

    public function testResetEmailService()
    {
        $emailService = $this->getMockBuilder(EmailService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $emailService->setMessageSubject('test Subject');
        $emailService->setMessageBody('test Body');
        $emailService->setMessageTo(['test@cia-ferias.local']);
        $emailService->setMessageCc(['test@cia-ferias.local']);
        $emailService->setMessageBcc(['test@cia-ferias.local']);

        $emailQueueService = $this->getMockBuilder(EmailQueueService::class)
            ->onlyMethods(['getEmailService'])
            ->getMock();
        $emailQueueService->expects($this->exactly(6))
            ->method('getEmailService')
            ->willReturn($emailService);
        $emailQueueService->resetEmailService();
    }

    public function testChangeMailStatus()
    {
        $this->createKernelWithMockServices([Services::DATETIME_HELPER_SERVICE => new DateTimeHelperService()]);
        $mail = new Mail();
        $mail->setSubject('test7 subject');
        $mail->setBody('test7 body');
        $mail->setToList(['test7@ciaferias.local', 'test8@ciaferias.local']);
        $mail->setCcList(['test9@ciaferias.local']);
        $mail->setBccList(['test10@ciaferias.local']);

        $emailQueueService = new EmailQueueService();
        $result = $emailQueueService->changeMailStatus($mail, Mail::STATUS_STARTED);
        $this->assertEquals(Mail::STATUS_STARTED, $result->getStatus());

        $result = $emailQueueService->changeMailStatus($mail, Mail::STATUS_SENT);
        $this->assertEquals(Mail::STATUS_SENT, $result->getStatus());
        $this->assertNotNull($result->getSentAt());
    }

    public function testSendAllPendingMails()
    {
        $this->createKernelWithMockServices([Services::DATETIME_HELPER_SERVICE => new DateTimeHelperService()]);
        $emailQueueDao = $this->getMockBuilder(EmailQueueDao::class)
            ->onlyMethods(['getAllPendingMailIds'])
            ->getMock();
        $emailQueueDao->expects($this->once())
            ->method('getAllPendingMailIds')
            ->willReturn([1, 2, 3]);

        $emailQueueService = $this->getMockBuilder(EmailQueueService::class)
            ->onlyMethods(['getEmailQueueDao', 'resetEmailService', 'sendSingleMail'])
            ->getMock();
        $emailQueueService->expects($this->once())
            ->method('getEmailQueueDao')
            ->willReturn($emailQueueDao);
        $emailQueueService->expects($this->exactly(3))
            ->method('resetEmailService');
        $emailQueueService->expects($this->exactly(3))
            ->method('sendSingleMail');
        $emailQueueService->sendAllPendingMails();
    }
}
