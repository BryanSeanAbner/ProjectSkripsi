<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DummyDataController extends Controller
{
    public function acu()
    {
        // Placeholders untuk layout (sementara pakai random order sampai tabel rekomendasi dari IPYNB di-export)
        $popularity = \Illuminate\Support\Facades\DB::table('articles')->inRandomOrder()->take(6)->get();
        $als = \Illuminate\Support\Facades\DB::table('articles')->inRandomOrder()->take(6)->get();
        $lightgcn = \Illuminate\Support\Facades\DB::table('articles')->inRandomOrder()->take(6)->get();
        
        $title = 'Aplikasi Rekomendasi (Dataset ACU)';
        return view('dummy.data', compact('popularity', 'als', 'lightgcn', 'title'));
    }

    public function density()
    {
        $popularity = \Illuminate\Support\Facades\DB::table('articles')->inRandomOrder()->take(6)->get();
        $als = \Illuminate\Support\Facades\DB::table('articles')->inRandomOrder()->take(6)->get();
        $lightgcn = \Illuminate\Support\Facades\DB::table('articles')->inRandomOrder()->take(6)->get();

        $title = 'Aplikasi Rekomendasi (Dataset Density)';
        return view('dummy.data', compact('popularity', 'als', 'lightgcn', 'title'));
    }
}
