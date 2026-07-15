/** Laravel's XSRF-TOKEN cookie is readable JS-side by design — axios/Inertia send it back as X-XSRF-TOKEN. */
export function xsrfHeader(): Record<string, string> {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? { 'X-XSRF-TOKEN': decodeURIComponent(match[1]) } : {};
}
