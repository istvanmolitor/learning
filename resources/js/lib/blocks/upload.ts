// A plain fetch() upload helper — this project has no axios dependency, and
// Inertia's own router always does a full page visit, which is wrong for
// "upload a file, get a path back" used inline inside a block's edit form.
// Laravel sets the XSRF-TOKEN cookie on every response by default; sending
// it back as a header is the standard way to authenticate a same-origin
// fetch() against CSRF protection without pulling in a new dependency.
function readXsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}

export async function uploadMedia(
    url: string,
    file: File,
): Promise<{ path: string; url: string }> {
    const form = new FormData();
    form.append('file', file);

    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'X-XSRF-TOKEN': readXsrfToken(),
            Accept: 'application/json',
        },
        body: form,
    });

    if (!response.ok) {
        throw new Error('A feltöltés nem sikerült.');
    }

    return response.json();
}
