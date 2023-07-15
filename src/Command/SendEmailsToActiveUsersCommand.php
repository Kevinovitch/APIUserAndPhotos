<?php

namespace App\Command;

use App\Service\UserService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'SendEmailsToActiveUsersCommand',
    description: 'Command to send an email to all active users ',
)]
class SendEmailsToActiveUsersCommand extends Command
{

    protected static $defaultName = 'app:send-emails';
    protected static $defaultDescription = 'Send emails to all active users created last week';

    private MailerInterface $mailer;
    private UserService $userService;

    public function __construct(MailerInterface $mailer, UserService $userService)
    {
        $this->mailer = $mailer;
        $this->userService = $userService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('time', 't', InputOption::VALUE_REQUIRED, 'The time to run the command (HH:MM)', '18:00')
            ->setDescription(self::$defaultDescription)
            ->setHelp('This command sends emails to all active users created last week.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sender = 'Cobbleweb';
        $time = $input->getOption('time');

        $io->title('Sending Emails to Active Users');
        $io->text(sprintf('Sender: %s', $sender));
        $io->text(sprintf('Time: %s', $time));

        $now = new \DateTime();
        $lastWeek = $now->modify('-1 week');

        $users = $this->userService->getActiveUsersCreatedAfter($lastWeek);

        foreach ($users as $user) {
            $email = (new Email())
                ->from($sender)
                ->to($user->getEmail())
                ->subject('Your best newsletter')
                ->text('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec id interdum nibh. Phasellus blandit tortor in cursus convallis. Praesent et tellus fermentum, pellentesque lectus at, tincidunt risus. Quisque in nisl malesuada, aliquet nibh at, molestie libero.');

            $this->mailer->send($email);

        }

        $io->success(sprintf('Emails sent to %d active users created last week.', count($users)));

        return Command::SUCCESS;
    }
}
