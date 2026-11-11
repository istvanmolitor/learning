<?php

use App\Mcp\Servers\CourseServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('mcp/courses', CourseServer::class)->middleware('auth:sanctum');
