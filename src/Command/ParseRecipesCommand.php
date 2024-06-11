<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:parse-recipes',
    description: 'Add a short description for your command',
)]
class ParseRecipesCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');

        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
        }

        if ($input->getOption('option1')) {
            // ...
        }
        
        // Construction du chemin du fichier JSON
        $projectDir = dirname(__DIR__, 2); // Remonte de deux niveaux pour atteindre le répertoire du projet
        $jsonPath = $projectDir . '/public/recipes.json';

        // Vérification de l'existence du fichier
        if (!file_exists($jsonPath)) {
            $io->error('File not found: ' . $jsonPath);
            return Command::FAILURE;
        }

        // Lecture et décodage du fichier JSON
        $jsonData = file_get_contents($jsonPath);
        $data = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $io->error('Error decoding JSON: ' . json_last_error_msg());
            return Command::FAILURE;
        }

        // Traitement des données
        if (isset($data['recipes'])) {
            $recipes = $data['recipes'];
            $recipeStrings = [];

            foreach ($recipes as $recipe) {
                // Formattage de chaque recette en chaîne de caractères lisible
                $recipeStrings[] = sprintf(
                    "Name: %s\nIngredients: %s\nPreparation Time: %s\nCooking Time: %s\nServes: %d",
                    $recipe['name'],
                    implode(', ', $recipe['ingredients']),
                    $recipe['preparationTime'],
                    $recipe['cookingTime'],
                    $recipe['serves']
                );
            }

            $io->success('Successfully parsed the recipes!');
            $io->listing($recipeStrings); // Affiche les recettes
        } else {
            $io->error('No recipes found in the JSON file.');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
