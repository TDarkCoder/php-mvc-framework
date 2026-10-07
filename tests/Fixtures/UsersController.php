<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Tests\Fixtures;

use TDarkCoder\Framework\Http\Controller;
use TDarkCoder\Framework\Http\Request;

class UsersController extends Controller
{
    protected array $middlewares = [
        RecordingMiddleware::class => 'index',
    ];

    public function index(Request $request): string
    {
        return 'users:index';
    }

    public function show(int $id, Request $request): string
    {
        return "users:show:$id";
    }

    public function store(Request $request): string
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        return 'stored ' . $data['email'];
    }
}
