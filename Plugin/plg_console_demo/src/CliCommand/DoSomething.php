<?php

namespace AKH\Plugin\Console\Demo\CliCommand;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Service\Provider\Database;
use Joomla\Database\DatabaseAwareTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseInterface;

class DoSomething extends AbstractCommand
{
    use DatabaseAwareTrait;

    protected static $defaultName = 'demo:dosomething';

    private $cliInput;

    private $ioStyle;

    public function __construct(DatabaseInterface $db)
    {
        parent::__construct();

        $this->setDatabase($db);
    }

    private function configureIO(InputInterface $input, OutputInterface $output): void
    {
        $this->cliInput = $input;
        $this->ioStyle  = new SymfonyStyle($input, $output);
    }

	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$this->configureIO($input, $output);

		//$this->ioStyle->title('This is the title.');

		// $this->ioStyle->info('Info');
        // $this->ioStyle->success('Success');
        // $this->ioStyle->error('Error');
        // $this->ioStyle->warning('Warning');
        // $this->ioStyle->note('Note');
        // $this->ioStyle->comment('Comment');

        $db = $this->getDatabase();

        $query = $db->getQuery(true);

        $query->select($db->quoteName('id'))
            ->from($db->quoteName('#__mytable'))
            ->order($db->quoteName('order') . ' ASC');

        $db->setQuery($query);

        $ids = $db->loadColumn();

        foreach ($ids as $id) {
            $this->ioStyle->text($id);
        }

        $client = HttpFactory::getHttp();

        $response = $client->get('https://www.duckduckgo.com');

        $result = $response->body;

        $this->ioStyle->text($result);

        return 0;
	}

	protected function configure(): void
	{
		$this->setDescription('💩');
		$this->setHelp('🤢');
	}
}
