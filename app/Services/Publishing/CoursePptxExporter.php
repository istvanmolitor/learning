<?php

namespace App\Services\Publishing;

use App\Models\Course;
use App\Models\Lesson;
use PhpOffice\PhpPresentation\DocumentLayout;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\Shape\Drawing\File as ImageShape;
use PhpOffice\PhpPresentation\Shape\Hyperlink;
use PhpOffice\PhpPresentation\Shape\RichText;
use PhpOffice\PhpPresentation\Slide;
use PhpOffice\PhpPresentation\Slide\Background\Color as SlideBackground;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Style\Font;

/**
 * Renders a course as a widescreen PowerPoint deck: a title slide, a table of
 * contents, one section-divider slide per lesson, one or more content slides
 * per page (split along the same ## boundaries MarkdownSplitter uses), and a
 * quiz slide per lesson that has questions. Shares its brand colors with the
 * PDF export (resources/views/pdf/course.blade.php) so both read as one family.
 *
 * YouTube videos cannot be embedded as playable elements through this library,
 * so they are rendered as a hyperlinked line in the slide body instead of being
 * silently dropped.
 */
class CoursePptxExporter
{
    private const BRAND = 'FF6D28D9';

    private const BRAND_LIGHT = 'FFF6F3FE';

    private const INK = 'FF1F1B2E';

    private const MUTED = 'FFE9D8FF';

    private const SLIDE_WIDTH = 720;

    private const SLIDE_HEIGHT = 405;

    private const MARGIN = 40;

    public function export(Course $course): string
    {
        $course->loadMissing(['lessons.pages', 'lessons.questions']);

        $presentation = new PhpPresentation;
        $presentation->getLayout()->setDocumentLayout(DocumentLayout::LAYOUT_SCREEN_16X9);
        $presentation->removeSlideByIndex(0);

        $this->addTitleSlide($presentation, $course);
        $this->addTocSlide($presentation, $course);

        foreach ($course->lessons as $lesson) {
            $this->addLessonDividerSlide($presentation, $lesson);

            foreach ($lesson->pages as $page) {
                foreach (MarkdownSlideSplitter::slides($page) as $slideData) {
                    $this->addContentSlide($presentation, $slideData);
                }
            }

            if ($lesson->questions->isNotEmpty()) {
                $this->addQuizSlide($presentation, $lesson);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'course-pptx-').'.pptx';
        IOFactory::createWriter($presentation, 'PowerPoint2007')->save($path);

        return $path;
    }

    private function addTitleSlide(PhpPresentation $presentation, Course $course): void
    {
        $slide = $presentation->createSlide();
        $slide->setBackground((new SlideBackground)->setColor(new Color(self::BRAND)));

        $kicker = $this->addText($slide, 'KURZUS', self::MARGIN, 90, self::SLIDE_WIDTH - 2 * self::MARGIN, 24);
        $this->styleRun($kicker, size: 12, bold: true, color: self::MUTED);

        $title = $this->addText($slide, $course->title, self::MARGIN, 130, self::SLIDE_WIDTH - 2 * self::MARGIN, 90);
        $this->styleRun($title, size: 34, bold: true, color: 'FFFFFFFF');

        if ($course->description) {
            $description = $this->addText($slide, $course->description, self::MARGIN, 230, self::SLIDE_WIDTH - 2 * self::MARGIN, 80);
            $this->styleRun($description, size: 14, color: self::MUTED);
        }

        $footer = $this->addText($slide, 'Exportálva: '.now()->translatedFormat('Y. F d.'), self::MARGIN, self::SLIDE_HEIGHT - 50, 300, 24);
        $this->styleRun($footer, size: 10, color: self::MUTED);
    }

    private function addTocSlide(PhpPresentation $presentation, Course $course): void
    {
        $slide = $presentation->createSlide();

        $heading = $this->addText($slide, 'Tartalom', self::MARGIN, self::MARGIN, 300, 40);
        $this->styleRun($heading, size: 24, bold: true, color: self::BRAND);

        $shape = $slide->createRichTextShape();
        $shape->setOffsetX(self::MARGIN);
        $shape->setOffsetY(self::MARGIN + 50);
        $shape->setWidth(self::SLIDE_WIDTH - 2 * self::MARGIN);
        $shape->setHeight(self::SLIDE_HEIGHT - self::MARGIN - 90);

        foreach ($course->lessons as $index => $lesson) {
            $paragraph = $index === 0 ? $shape->getActiveParagraph() : $shape->createParagraph();
            $run = $paragraph->createTextRun(($index + 1).'. '.$lesson->title);
            $run->getFont()->setSize(16)->setColor(new Color(self::INK));
        }
    }

    private function addLessonDividerSlide(PhpPresentation $presentation, Lesson $lesson): void
    {
        $slide = $presentation->createSlide();
        $slide->setBackground((new SlideBackground)->setColor(new Color(self::BRAND)));

        $kicker = $this->addText($slide, 'LECKE '.($lesson->order + 1), self::MARGIN, 150, self::SLIDE_WIDTH - 2 * self::MARGIN, 24);
        $this->styleRun($kicker, size: 12, bold: true, color: self::MUTED, align: Alignment::HORIZONTAL_CENTER);

        $title = $this->addText($slide, $lesson->title, self::MARGIN, 185, self::SLIDE_WIDTH - 2 * self::MARGIN, 70);
        $this->styleRun($title, size: 30, bold: true, color: 'FFFFFFFF', align: Alignment::HORIZONTAL_CENTER);
    }

    /**
     * @param  array{title: string, text: string, image_path: ?string, video: ?array{title: string, url: string}}  $slideData
     */
    private function addContentSlide(PhpPresentation $presentation, array $slideData): void
    {
        $slide = $presentation->createSlide();

        $heading = $this->addText($slide, $slideData['title'], self::MARGIN, self::MARGIN, self::SLIDE_WIDTH - 2 * self::MARGIN, 36);
        $this->styleRun($heading, size: 22, bold: true, color: self::BRAND);

        $contentTop = self::MARGIN + 50;
        $contentHeight = self::SLIDE_HEIGHT - $contentTop - self::MARGIN;
        $hasImage = $slideData['image_path'] !== null;
        $textWidth = $hasImage ? 380 : self::SLIDE_WIDTH - 2 * self::MARGIN;

        $textShape = $slide->createRichTextShape();
        $textShape->setOffsetX(self::MARGIN);
        $textShape->setOffsetY($contentTop);
        $textShape->setWidth($textWidth);
        $textShape->setHeight($contentHeight);
        $textShape->setAutoFit(RichText::AUTOFIT_NORMAL);

        $paragraphs = array_filter(preg_split('/\n{2,}/', $slideData['text']) ?: []);
        $first = true;

        foreach ($paragraphs as $paragraphText) {
            $paragraph = $first ? $textShape->getActiveParagraph() : $textShape->createParagraph();
            $first = false;
            $run = $paragraph->createTextRun(trim($paragraphText));
            $run->getFont()->setSize(14)->setColor(new Color(self::INK));
            $paragraph->setLineSpacing(130);
        }

        if ($slideData['video'] !== null) {
            $paragraph = $first ? $textShape->getActiveParagraph() : $textShape->createParagraph();
            $run = $paragraph->createTextRun('▶ Videó: '.$slideData['video']['title']);
            $run->getFont()->setSize(13)->setBold(true)->setColor(new Color(self::BRAND))->setUnderline(Font::UNDERLINE_SINGLE);
            $run->setHyperlink(new Hyperlink($slideData['video']['url'], 'Megnyitás a YouTube-on'));
        }

        if ($slideData['image_path'] !== null) {
            $image = new ImageShape;
            $image->setPath($slideData['image_path']);
            $image->setResizeProportional(true);
            $image->setHeight($contentHeight);

            $imageColumnX = self::MARGIN + $textWidth + 20;
            $imageColumnWidth = self::SLIDE_WIDTH - self::MARGIN - $imageColumnX;

            if ($image->getWidth() > $imageColumnWidth) {
                $image->setWidth($imageColumnWidth);
            }

            $image->setOffsetX((int) ($imageColumnX + max(0, ($imageColumnWidth - $image->getWidth()) / 2)));
            $image->setOffsetY((int) ($contentTop + max(0, ($contentHeight - $image->getHeight()) / 2)));
            $slide->addShape($image);
        }
    }

    private function addQuizSlide(PhpPresentation $presentation, Lesson $lesson): void
    {
        $slide = $presentation->createSlide();
        $slide->setBackground((new SlideBackground)->setColor(new Color(self::BRAND_LIGHT)));

        $heading = $this->addText($slide, 'Ellenőrző kérdések', self::MARGIN, self::MARGIN, self::SLIDE_WIDTH - 2 * self::MARGIN, 40);
        $this->styleRun($heading, size: 22, bold: true, color: self::BRAND);

        $shape = $slide->createRichTextShape();
        $shape->setOffsetX(self::MARGIN);
        $shape->setOffsetY(self::MARGIN + 50);
        $shape->setWidth(self::SLIDE_WIDTH - 2 * self::MARGIN);
        $shape->setHeight(self::SLIDE_HEIGHT - self::MARGIN - 90);

        foreach ($lesson->questions as $index => $question) {
            $paragraph = $index === 0 ? $shape->getActiveParagraph() : $shape->createParagraph();
            $run = $paragraph->createTextRun(($index + 1).'. '.$question->text);
            $run->getFont()->setSize(15)->setColor(new Color(self::INK));
            $paragraph->setLineSpacing(150);
        }
    }

    private function addText(Slide $slide, string $text, int $x, int $y, int $width, int $height): RichText
    {
        $shape = $slide->createRichTextShape();
        $shape->setOffsetX($x);
        $shape->setOffsetY($y);
        $shape->setWidth($width);
        $shape->setHeight($height);
        $shape->getActiveParagraph()->createTextRun($text);

        return $shape;
    }

    private function styleRun(RichText $shape, int $size, string $color, bool $bold = false, string $align = Alignment::HORIZONTAL_LEFT): void
    {
        $paragraph = $shape->getActiveParagraph();
        $paragraph->getAlignment()->setHorizontal($align);

        $run = $paragraph->getRichTextElements()[0];
        $run->getFont()->setSize($size)->setBold($bold)->setColor(new Color($color));
    }
}
