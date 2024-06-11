<?php

namespace App\Command;

use App\Services\RecipeImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:parse-recipes',
    description: 'Parse recipes from JSON and save to database',
)]
class ParseRecipesCommand extends Command
{
    private RecipeImportService $recipeImportService;

    public function __construct(RecipeImportService $recipeImportService)
    {
        parent::__construct();
        $this->recipeImportService = $recipeImportService;
    }

    protected function configure(): void
    {
        // Configuration can be added here if needed
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->recipeImportService->importFromJson(dirname(__DIR__, 2) . '/public/recipes.json');
            $io->success('Recipes have been successfully imported.');
        } catch (\Exception $e) {
            $io->error('Error importing recipes: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
