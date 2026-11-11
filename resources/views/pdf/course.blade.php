<!doctype html>
<html lang="hu">
<head>
<meta charset="utf-8">
<title>{{ $course->title }}</title>
<style>
    @page {
        margin: 80px 50px 55px 50px;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 11px;
        line-height: 1.6;
        color: #262433;
    }

    /* ---------- cover ---------- */
    .cover {
        width: 100%;
        background-color: #6d28d9;
        color: #ffffff;
        border-radius: 18px;
        padding: 70px 50px;
        margin-bottom: 20px;
    }

    .cover-kicker {
        display: inline-block;
        font-size: 10px;
        letter-spacing: 0.22em;
        font-weight: bold;
        color: #e9d8ff;
        background: rgba(255, 255, 255, 0.14);
        padding: 6px 14px;
        border-radius: 999px;
        margin-bottom: 30px;
    }

    .cover-title {
        font-size: 32px;
        line-height: 1.3;
        font-weight: bold;
        margin: 0 0 20px 0;
        color: #ffffff;
    }

    .cover-rule {
        width: 70px;
        height: 4px;
        background: #ffffff;
        border-radius: 2px;
        margin: 0 0 24px 0;
    }

    .cover-desc {
        font-size: 13px;
        line-height: 1.7;
        color: #ede9fe;
        max-width: 420px;
        margin: 0 0 50px 0;
    }

    .cover-stats {
        display: table;
        width: auto;
        border-collapse: separate;
        margin-bottom: 40px;
    }

    .cover-stat {
        display: table-cell;
        padding: 0 34px 0 0;
    }

    .cover-stat-number {
        display: block;
        font-size: 24px;
        font-weight: bold;
        color: #ffffff;
    }

    .cover-stat-label {
        display: block;
        font-size: 9px;
        letter-spacing: 0.12em;
        color: #e9d8ff;
        margin-top: 2px;
    }

    .cover-footer {
        font-size: 9px;
        color: #e9d8ff;
        border-top: 1px solid rgba(255, 255, 255, 0.28);
        padding-top: 14px;
    }

    /* ---------- table of contents ---------- */
    .toc {
        page-break-before: always;
    }

    .toc-heading {
        font-size: 10px;
        letter-spacing: 0.2em;
        color: #7c3aed;
        font-weight: bold;
        margin: 0 0 4px 0;
    }

    .toc-title {
        font-size: 22px;
        font-weight: bold;
        color: #1f1b2e;
        margin: 0 0 26px 0;
    }

    .toc-lesson {
        display: table;
        width: 100%;
        margin-bottom: 14px;
        padding-bottom: 10px;
        border-bottom: 1px solid #ece8f7;
    }

    .toc-lesson-index {
        display: table-cell;
        width: 28px;
        vertical-align: top;
        font-size: 13px;
        font-weight: bold;
        color: #7c3aed;
    }

    .toc-lesson-body {
        display: table-cell;
        vertical-align: top;
    }

    .toc-lesson-title {
        display: block;
        font-size: 13px;
        font-weight: bold;
        color: #1f1b2e;
        margin-bottom: 4px;
    }

    .toc-page-item {
        font-size: 10px;
        color: #6b6678;
        margin: 0 0 2px 0;
    }

    .toc-page-item:before {
        content: "– ";
        color: #c4b5fd;
    }

    /* ---------- lessons ---------- */
    .lesson {
        page-break-before: always;
    }

    .lesson-header {
        display: table;
        width: 100%;
        margin-bottom: 26px;
        padding-bottom: 16px;
        border-bottom: 2px solid #7c3aed;
    }

    .lesson-number-badge {
        display: table-cell;
        width: 40px;
        vertical-align: middle;
    }

    .lesson-number-badge span {
        display: inline-block;
        width: 30px;
        height: 30px;
        line-height: 30px;
        text-align: center;
        border-radius: 999px;
        background: #7c3aed;
        color: #ffffff;
        font-weight: bold;
        font-size: 13px;
    }

    .lesson-title-cell {
        display: table-cell;
        vertical-align: middle;
        padding-left: 6px;
    }

    .lesson-title {
        font-size: 19px;
        font-weight: bold;
        color: #1f1b2e;
        margin: 0;
    }

    .page-block {
        margin-bottom: 26px;
    }

    .page-title {
        font-size: 14px;
        font-weight: bold;
        color: #4c1d95;
        margin: 0 0 10px 0;
        padding-left: 10px;
        border-left: 3px solid #c4b5fd;
    }

    /* ---------- prose content ---------- */
    .prose h1, .prose h2, .prose h3, .prose h4 {
        color: #1f1b2e;
        font-weight: bold;
        margin: 14px 0 8px 0;
    }

    .prose h1 { font-size: 18px; }
    .prose h2 { font-size: 15px; }
    .prose h3 { font-size: 13px; }
    .prose h4 { font-size: 12px; }

    .prose p {
        margin: 0 0 10px 0;
    }

    .prose ul, .prose ol {
        margin: 0 0 10px 0;
        padding-left: 20px;
    }

    .prose li {
        margin-bottom: 4px;
    }

    .prose blockquote {
        margin: 0 0 10px 0;
        padding: 8px 14px;
        border-left: 3px solid #c4b5fd;
        background: #f6f3fe;
        color: #4c1d95;
    }

    .prose a {
        color: #7c3aed;
        text-decoration: underline;
    }

    .prose strong {
        color: #1f1b2e;
    }

    /* ---------- quiz questions ---------- */
    .quiz {
        margin-top: 10px;
        padding: 14px 18px;
        background: #f6f3fe;
        border-radius: 10px;
    }

    .quiz-title {
        font-size: 13px;
        font-weight: bold;
        color: #4c1d95;
        margin: 0 0 8px 0;
    }

    .quiz-question {
        margin: 0 0 8px 0;
        font-size: 11px;
        color: #1f1b2e;
    }

    .quiz-question-number {
        font-weight: bold;
        color: #7c3aed;
    }

    /* ---------- image block ---------- */
    .image-block {
        margin: 0 0 16px 0;
        text-align: center;
    }

    .image-block img {
        max-width: 100%;
        border-radius: 8px;
    }

    .image-block figcaption {
        font-size: 9px;
        color: #8a8699;
        margin-top: 6px;
    }

    /* ---------- video card ---------- */
    .video-card {
        display: table;
        width: 100%;
        margin: 0 0 16px 0;
        background: #f6f3fe;
        border: 1px solid #e3d9fb;
        border-radius: 10px;
        text-decoration: none;
    }

    .video-card-icon {
        display: table-cell;
        width: 44px;
        vertical-align: middle;
        text-align: center;
        color: #ffffff;
    }

    .video-card-icon span {
        display: inline-block;
        width: 26px;
        height: 26px;
        line-height: 26px;
        border-radius: 999px;
        background: #7c3aed;
        color: #ffffff;
        font-size: 10px;
    }

    .video-card-body {
        display: table-cell;
        vertical-align: middle;
        padding: 12px 14px 12px 4px;
    }

    .video-card-label {
        display: block;
        font-size: 8px;
        letter-spacing: 0.16em;
        color: #7c3aed;
        font-weight: bold;
        margin-bottom: 2px;
    }

    .video-card-title {
        display: block;
        font-size: 11px;
        font-weight: bold;
        color: #1f1b2e;
        margin-bottom: 2px;
    }

    .video-card-url {
        display: block;
        font-size: 9px;
        color: #6b6678;
    }
</style>
</head>
<body>

<section class="cover">
    <div class="cover-kicker">E-LEARNING KURZUS</div>
    <h1 class="cover-title">{{ $course->title }}</h1>
    <div class="cover-rule"></div>

    @if ($course->description)
        <p class="cover-desc">{{ $course->description }}</p>
    @endif

    <div class="cover-stats">
        <div class="cover-stat">
            <span class="cover-stat-number">{{ $course->lessons->count() }}</span>
            <span class="cover-stat-label">LECKE</span>
        </div>
        <div class="cover-stat">
            <span class="cover-stat-number">{{ $totalPages }}</span>
            <span class="cover-stat-label">OLDAL</span>
        </div>
    </div>

    <div class="cover-footer">Exportálva: {{ $generatedAt }}</div>
</section>

<section class="toc">
    <div class="toc-heading">TARTALOM</div>
    <h2 class="toc-title">A kurzus felépítése</h2>

    @foreach ($course->lessons as $lesson)
        <div class="toc-lesson">
            <div class="toc-lesson-index">{{ $loop->iteration }}.</div>
            <div class="toc-lesson-body">
                <span class="toc-lesson-title">{{ $lesson->title }}</span>
                @foreach ($lesson->pages as $page)
                    <div class="toc-page-item">{{ $page->title }}</div>
                @endforeach
            </div>
        </div>
    @endforeach
</section>

@foreach ($course->lessons as $lesson)
    <section class="lesson">
        <div class="lesson-header">
            <div class="lesson-number-badge"><span>{{ $loop->iteration }}</span></div>
            <div class="lesson-title-cell">
                <h2 class="lesson-title">{{ $lesson->title }}</h2>
            </div>
        </div>

        @foreach ($lesson->pages as $page)
            <article class="page-block">
                <h3 class="page-title">{{ $page->title }}</h3>

                <div class="prose">{!! \App\Services\Publishing\PageHtml::forPdf($page) !!}</div>
            </article>
        @endforeach

        @if ($lesson->questions->isNotEmpty())
            <div class="quiz">
                <p class="quiz-title">Ellenőrző kérdések</p>
                @foreach ($lesson->questions as $question)
                    <p class="quiz-question">
                        <span class="quiz-question-number">{{ $loop->iteration }}.</span>
                        {{ $question->text }}
                    </p>
                @endforeach
            </div>
        @endif
    </section>
@endforeach

</body>
</html>
