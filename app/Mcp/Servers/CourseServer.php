<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreateLessonTool;
use App\Mcp\Tools\CreatePageTool;
use App\Mcp\Tools\DeleteItemTool;
use App\Mcp\Tools\ExportCourseTool;
use App\Mcp\Tools\GetCourseStructureTool;
use App\Mcp\Tools\GetPageMarkdownTool;
use App\Mcp\Tools\ImportCourseTool;
use App\Mcp\Tools\ListCoursesTool;
use App\Mcp\Tools\RenameItemTool;
use App\Mcp\Tools\ReorderItemsTool;
use App\Mcp\Tools\SetQuestionsTool;
use App\Mcp\Tools\UpdateCourseMetaTool;
use App\Mcp\Tools\UpdatePageMarkdownTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Course Editor')]
#[Version('1.0.0')]
#[Instructions(
    'Tools for reading and editing courses on this e-learning platform. Courses are made of lessons, '.
    'each holding Markdown pages and (optionally) quiz questions shown after the lesson\'s pages. Every '.
    'tool requires the manage-courses permission and takes effect immediately, including on published '.
    'courses — there is no draft/review step. Start with list_courses and get_course_structure to find '.
    'slugs before editing anything.'
)]
class CourseServer extends Server
{
    protected array $tools = [
        ListCoursesTool::class,
        GetCourseStructureTool::class,
        GetPageMarkdownTool::class,
        UpdatePageMarkdownTool::class,
        CreateLessonTool::class,
        CreatePageTool::class,
        RenameItemTool::class,
        ReorderItemsTool::class,
        DeleteItemTool::class,
        UpdateCourseMetaTool::class,
        SetQuestionsTool::class,
        ExportCourseTool::class,
        ImportCourseTool::class,
    ];
}
