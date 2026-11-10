<?php declare(strict_types=1);

namespace RatingStar\Seal\Command;

use RatingStar\Seal\Service\RatingStarClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Manual cache-clear path, e.g. after rotating the API key in RatingStar:
 *
 *   bin/console ratingstar:seal:cache:clear
 */
#[AsCommand(
    name: 'ratingstar:seal:cache:clear',
    description: 'Flush the cached RatingStar JSON-LD payloads and the cached pages showing a seal.',
)]
class ClearCacheCommand extends Command
{
    public function __construct(private readonly RatingStarClient $client)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->client->clearCache();

        (new SymfonyStyle($input, $output))->success('RatingStar seal cache cleared.');

        return Command::SUCCESS;
    }
}
