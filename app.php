#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use App\DueDateCalculator;
use App\IssueValidator;
use App\Model\Issue;
use App\WorkdaysConfiguration;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

$app = new Application();

$app->register('calculate-due-date')
    ->addUsage('Usage: calculate-due-date')
    ->addArgument('issue-date', InputArgument::OPTIONAL, 'Date of an issue in "yyyy-mm-dd" format')
    ->addArgument('issue-time', InputArgument::OPTIONAL, 'Time of an issue in "hh:mm" format')
    ->addArgument('lead-time', InputArgument::OPTIONAL, 'Lead time hours')
    ->setDescription('Calculate due date of on issue given by issue date, issue time and the lead time in hours')
    ->setCode(function (InputInterface $input, OutputInterface $output): int {
        if (null === $input->getArgument('issue-date')
                || null === $input->getArgument('issue-time')
                || null === $input->getArgument('lead-time')) {
            interact($input, $output, $this->getHelper('question'));
        }

        try {
            $workdays = Yaml::parseFile(__DIR__.'/config/workdays.yaml');

            if (!\array_key_exists('working_hours', $workdays)) {
                throw new \RuntimeException('Invalid or missing configuration!');
            }
        } catch (ParseException $e) {
            throw new \RuntimeException(\sprintf('Missing or invalid configuration file: %s', $e->getMessage()));
        }

        $configuration = new WorkdaysConfiguration($workdays['working_hours']);


        $issueDate = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $input->getArgument('issue-date').' '.$input->getArgument('issue-time'));
        if (false === $issueDate) {
            throw new \InvalidArgumentException('Invalid arguments: issue-date, issue-time');
        }

        $issue = new Issue($issueDate, (int)$input->getArgument('lead-time'));

        $issueValidator = new IssueValidator($configuration);

        if (!$issueValidator->dateIsValid($issue)) {
            if (!$configuration->isWorkingDay($issue->getDate())) {
                $output->writeln(\sprintf('<error>Given date "%s" is not a working day.</error>', $issue->getDate()->format('Y-m-d')));
            } else {
                $workingDay = $configuration->getWorkingDay($issue->getDate());
                $output->writeln(\sprintf('<error>Given time is out of working hours. Working hours are %s-%s.</error>',
                    $workingDay->getStart()->format('H:i'),
                    $workingDay->getEnd()->format('H:i')
                ));
            }

            return 1;
        }

        $calculator = new DueDateCalculator($configuration);

        $dueDate = $calculator->calculateDueDate($issue);

        $output->writeln('');
        $output->writeln(\sprintf('<comment>Issue recorded at:</comment> %s', $issueDate->format('Y-m-d H:i')));
        $output->writeln(\sprintf('<comment>Lead time in hours:</comment> %d', $issue->getLeadTimeInHours()));
        $output->writeln(\sprintf('<info>Calculated due date:</info> %s', $dueDate->format('Y-m-d H:i')));

        return 0;
    })
;

function interact(InputInterface $input, OutputInterface $output, QuestionHelper $helper): void
{
    $issueDate = null;
    do {
        $question = new Question('<question>Please enter the date of the issue (format: "YYYY-MM-DD"): ');
        $answer = $helper->ask($input, $output, $question);
        if (false !== \DateTimeImmutable::createFromFormat('Y-m-d', $answer)) {
            $issueDate = $answer;
        } else {
            $output->writeln('<error>The entered value is invalid, please try again!');
        }
    } while (null === $issueDate);

    $issueTime = null;
    do {
        $question = new Question('<question>Please enter the time of the issue (format: "HH:MM"): ');
        $answer = $helper->ask($input, $output, $question);
        if (false !== \DateTimeImmutable::createFromFormat('H:i', $answer)) {
            $issueTime = $answer;
        } else {
            $output->writeln('<error>The entered value is invalid, please try again!');
        }
    } while (null === $issueTime);

    $leadTime = null;
    do {
        $question = new Question('<question>Please enter the lead time of the issue (in hours): ');
        $answer = $helper->ask($input, $output, $question);
        if (\is_numeric($answer)) {
            $leadTime = $answer;
        } else {
            $output->writeln('<error>The entered value is invalid, please try again!');
        }
    } while (null === $leadTime);

    $input->setArgument('issue-date', $issueDate);
    $input->setArgument('issue-time', $issueTime);
    $input->setArgument('lead-time', $leadTime);
}

$app->run();
