<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\News;

class NewsController extends Controller
{
    /** Pública: a página inicial também aparece para quem não está logado. */
    public function index(): void
    {
        $this->json([
            'source' => ['name' => News::SOURCE_NAME, 'url' => News::SOURCE_URL],
            'items' => News::latest(),
        ]);
    }
}
