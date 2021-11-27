<?php


namespace App\Command;


use App\Entity\Product;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CsvImportCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();

        $this->em = $em;
    }

    protected function configure()
    {
        $this
            ->setName('csv:import')
            ->setDescription('Import data from CSV')
            ->setHelp('This command allows you to import products data from a CSV-file')
            ->addArgument('path', InputArgument::REQUIRED, 'The path to the csv file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Attempting to import the data...');

        #current path: %kernel.project_dir%/../public/csv/products.csv
        #the command from console : php bin/console csv:import %kernel.project_dir%/../public/csv/products.csv

        $reader = Reader:: createFromPath($input->getArgument('path'));

        $results = $reader->fetchAssoc();//iterator

        $io->progressStart(iterator_count($results));
        foreach ($results as $row) {

            $product = (new Product())
                ->setCode($row['code'])
                ->setName($row['name'])
                ->setCategory($row['category'])
                ->setPrice($row['price'])
                ->setDescription($row['description'])
                ->setProductImage($row['product_image'])
                ->setCreatedAt(new DateTime(null, new DateTimeZone('Europe/Athens')));

            $this->em->persist($product);

            $io->progressAdvance();
        }

        $io->progressFinish();
        $this->em->flush();

        $io->success('Data imported!');

        return Command::SUCCESS;

    }

}

