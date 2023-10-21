<?php

namespace TDarkCoder\Framework\Database;

interface Migration
{
    public function up(): string;

    public function down(): string;
}