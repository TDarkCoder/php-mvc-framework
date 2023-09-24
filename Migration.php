<?php

namespace TDarkCoder\Framework;

interface Migration
{
    public function up(): string;

    public function down(): string;
}