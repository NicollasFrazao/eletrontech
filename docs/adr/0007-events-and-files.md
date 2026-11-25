# 0007. Events and files owned by their person, file contents under random keys, an allow-list read from the content

- Status: Accepted
- Date: 2026-11-26
- Issues: #10 (E7, events and file manager)

## Context

2015's `Eventos.php` kept a person's agenda (`tb_evento`: name, date, time, description) and exported
it to PDF (mPDF) or TXT into the person's files. `Arquivos.php` was a file manager (`tb_pasta` nested
by `cd_subpasta`, `tb_arquivo`) over a directory per person under the web root.

It had the flaws `security.md` lists:

- names and paths from the request were joined into file-system paths;
- uploads were kept under their own names, next to code the server would run;
- downloads blocked only `.php` and `.html`;
- the target folder or file came from the request, without checking whose it was.

## Decision

- **Two domains:**
  - `app/Domain/Agenda/`: `Event`, `EventDetails` (title up to 50 characters, a date and time that
    exist on the calendar, description up to 500), `EventRepository`, and `EventDocument` (PDF or
    TXT).
  - `app/Domain/Files/`:
    - `Folder` and `StoredFile` (with its `FileEntry`);
    - `EntryName`: up to 100 characters; no `/ \ : * ? " < > |`, no control characters, and not `.`
      or `..`;
    - `FileType`, the allow-list;
    - `StorageLimit`: 10 MiB a file, 100 MiB a person;
    - the ports `FileIndex` (names and folders), `FileStore` (bytes) and `Archiver` (zip).
- **Ownership is in every query.** Each method of `EventRepository` and `FileIndex` takes the owner, and
  another person's event, folder or file is **not found** (404), never forbidden, so its existence does
  not show. The controllers take the owner from the session only.
- **A name is only a label.** Contents are stored by `DiskFileStore` on the private `local` disk under
  `files/<40 random characters>`. No name, path or extension from the request ever reaches the file
  system, so path traversal has nothing to reach. Folders and files are rows, and a zip is built from
  the rows' names.
- **The allow-list is checked on the content.** The extension must be one of PDF, TXT, CSV, PNG, JPEG,
  GIF, WebP, DOCX, XLSX, ODT and ODS. The media type libmagic reads from the bytes (`finfo`, not the
  browser's claim nor the extension) must be of that kind. A PHP script named `.pdf` or an HTML page
  named `.txt` is refused, and SVG is left out because it can carry scripts.
- **Downloads are attachments:** the kind's media type, `Content-Disposition: attachment`, and
  `X-Content-Type-Options: nosniff`, so a browser saves a file and never runs or renders it in place.
- **Folder rules:**
  - a name is unique among the folders and files of one folder, in the database's collation;
  - deleting a folder deletes what is under it (foreign keys) and the contents (their keys are
    collected first);
  - a folder downloads as a zip with its tree.
- **Export:** every event goes to `events-<date>-<time>.pdf|txt` in the folder "Events" of the root,
  created on first use, as 2015 did. A file of the root named "Events" sends the export to the root.
  The PDF is made by **Dompdf** from a Blade page, with remote files and PHP off. 2015 used mPDF,
  whose GPL does not fit this repository's MIT licence; Dompdf is LGPL.
- **Pages:** plain Blade until E9: the month view with previous and next, a search, the event form,
  and the folder view (path, contents, upload, new folder, usage).

## Alternatives considered

- **Files on disk under their names in a folder per person**, as 2015 did. Every name would then be a
  path to sanitise; random keys make that unnecessary.
- **Trusting the extension, or the browser's media type.** Both are typed by whoever uploads.
- **A block-list** (refuse `.php`, `.html`, `.svg`…). It leaks every type nobody thought of; an
  allow-list fails closed.
- **403 for another person's file.** It confirms the id exists; 404 tells nothing.
- **mPDF.** It is GPL.

## Consequences

- E7 requires a signed-in, confirmed account. 2015 also required a licence for events and files.
  Here they are account features, not tools, so the `use-tool` gate of E5 does not apply. This is
  recorded in tech-debt for the owner to confirm.
- `ext-zip` (already in the image and the CI) and `ext-fileinfo` (built into PHP) are used. Dompdf
  joins the dependencies.
- E8's admin panel can read `files` and `events` for statistics; nothing in E7 depends on it.
