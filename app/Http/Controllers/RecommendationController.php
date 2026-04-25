<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateRecommendationsJob;
use App\Models\ArticleSimilarity;
use App\Models\RecommendationHistory;
use App\Models\TopTrending;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecommendationController extends Controller
{
    /**
     * Dashboard utama — tampilkan hasil rekomendasi user yang sudah ada.
     * Halaman ini akan diupdate secara real-time oleh Supabase JS client.
     */
    public function index()
    {
        $user   = Auth::user();
        $userId = $user->user_id;

        // Popularity — Top Trending
        $popularity = TopTrending::where('user_id', $userId)
            ->orderBy('rank_position')
            ->with('article.section')
            ->get();

        // CBF — Article Similarity
        // Ambil artikel yang pernah dibaca user untuk dapat referensi article_id-nya
        $userReadArticleIds = \DB::table('user_interaction')
            ->where('user_id', $userId)
            ->pluck('article_id');

        $cbf = ArticleSimilarity::whereIn('article_id', $userReadArticleIds)
            ->orderBy('rank_position')
            ->limit(5)
            ->with('similarArticle.section')
            ->get();

        // LightGCN — Recommendation History
        $lightgcn = RecommendationHistory::where('user_id', $userId)
            ->orderBy('rank_position')
            ->with('article.section')
            ->get();

        return view('dashboard.index', compact(
            'user', 'popularity', 'cbf', 'lightgcn'
        ));
    }

    /**
     * Trigger manual: generate ulang rekomendasi untuk user yang sedang login.
     */
    public function regenerate(Request $request)
    {
        $userId = Auth::user()->user_id;
        GenerateRecommendationsJob::dispatch($userId);

        return response()->json([
            'status'  => 'queued',
            'message' => "Recommendation generation queued for user {$userId}",
        ]);
    }

    /**
     * API: Cek apakah rekomendasi sudah tersedia untuk user saat ini.
     * Digunakan sebagai fallback jika Realtime tidak tersambung.
     */
    public function status()
    {
        $userId = Auth::user()->user_id;

        $popularityCount = TopTrending::where('user_id', $userId)->count();
        $cbfCount        = ArticleSimilarity::count(); // global
        $lightgcnCount   = RecommendationHistory::where('user_id', $userId)->count();

        return response()->json([
            'popularity_ready' => $popularityCount > 0,
            'cbf_ready'        => $cbfCount > 0,
            'lightgcn_ready'   => $lightgcnCount > 0,
            'all_ready'        => $popularityCount > 0 && $cbfCount > 0 && $lightgcnCount > 0,
        ]);
    }

    /**
     * API: Ambil data rekomendasi terbaru untuk user (JSON — untuk AJAX refresh).
     */
    public function data()
    {
        $userId = Auth::user()->user_id;

        $userReadArticleIds = \DB::table('user_interaction')
            ->where('user_id', $userId)
            ->pluck('article_id');

        $popularity = TopTrending::where('user_id', $userId)
            ->orderBy('rank_position')
            ->with('article.section')
            ->get()
            ->map(fn ($t) => $this->formatArticle($t->article, $t->rank_position, $t->view_count));

        $cbf = ArticleSimilarity::whereIn('article_id', $userReadArticleIds)
            ->orderBy('rank_position')
            ->limit(5)
            ->with('similarArticle.section')
            ->get()
            ->map(fn ($s) => $this->formatArticle($s->similarArticle, $s->rank_position));

        $lightgcn = RecommendationHistory::where('user_id', $userId)
            ->orderBy('rank_position')
            ->with('article.section')
            ->get()
            ->map(fn ($r) => $this->formatArticle($r->article, $r->rank_position));

        return response()->json([
            'popularity' => $popularity,
            'cbf'        => $cbf,
            'lightgcn'   => $lightgcn,
        ]);
    }

    private function formatArticle(?Article $article, int $rank, ?int $viewCount = null): array
    {
        if (! $article) return [];
        return [
            'article_id'   => $article->article_id,
            'title'        => $article->title,
            'photo_url'    => $article->photo_url,
            'publish_date' => $article->publish_date?->format('d M Y'),
            'url'          => $article->url,
            'section_name' => $article->section?->section_name ?? 'Umum',
            'rank'         => $rank,
            'view_count'   => $viewCount,
        ];
    }
}
