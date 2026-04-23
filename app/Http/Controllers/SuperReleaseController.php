<?php

namespace App\Http\Controllers;

use App\Services\ReleasePublisherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Client\RequestException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class SuperReleaseController extends Controller
{
    public function __construct(private readonly ReleasePublisherService $publisher)
    {
    }

    public function index(): Response
    {
        $configured = $this->publisher->isConfigured();

        $releases = [];
        $error = null;

        if ($configured) {
            try {
                $releases = $this->publisher->listReleases(15);
            } catch (RequestException $e) {
                $error = 'GitHub API error: ' . $e->getMessage();
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return Inertia::render('Super/Releases', [
            'configured' => $configured,
            'releases' => $releases,
            'error' => $error,
            'asset_name' => (string) config('system_update.github.asset_name', 'release.zip'),
        ]);
    }

    public function publish(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tag' => 'required|string|max:100',
        ]);

        try {
            $result = $this->publisher->triggerBuild($data['tag']);
        } catch (RequestException $e) {
            return response()->json([
                'message' => $this->friendlyGithubError($e, 'dispatching build workflow'),
            ], 502);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result, 202);
    }

    /**
     * Translates a raw GitHub HTTP error into something a human can act on.
     * The most common one is 403 "Resource not accessible by personal access
     * token" — which almost always means the configured PAT is missing the
     * contents:write and/or actions:write scopes, not that the app is broken.
     */
    private function friendlyGithubError(RequestException $e, string $context): string
    {
        $status = $e->response?->status();
        $bodyMsg = $e->response?->json('message');

        if ($status === 403) {
            return "GitHub refused the request while {$context} (403). "
                . "The PAT in UPDATE_GITHUB_PUBLISH_TOKEN needs 'Contents: Read and write' "
                . "and 'Actions: Read and write' (or the classic `repo` + `workflow` scopes). "
                . "Regenerate the token with those permissions, update .env, then run "
                . "`php artisan config:clear`."
                . ($bodyMsg ? " GitHub said: {$bodyMsg}" : '');
        }

        if ($status === 401) {
            return "GitHub rejected the PAT (401). Make sure UPDATE_GITHUB_PUBLISH_TOKEN "
                . "is set and hasn't expired.";
        }

        if ($status === 404) {
            return "GitHub returned 404 while {$context}. Check UPDATE_GITHUB_OWNER / "
                . "UPDATE_GITHUB_REPO, and that the PAT has access to that repo.";
        }

        return "GitHub API error while {$context} ({$status}): "
            . ($bodyMsg ?? $e->getMessage());
    }

    /**
     * Create a brand-new GitHub Release from inside the app, then optionally
     * kick off the build workflow so release.zip is produced without any
     * further clicks. Tag must match semver-ish ("v1.2.3"); release-please
     * uses the same convention so this stays consistent with the rest of
     * the pipeline.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tag'        => ['required', 'string', 'max:100', 'regex:/^v?\d+(\.\d+){0,2}(-[A-Za-z0-9.-]+)?$/'],
            'name'       => ['nullable', 'string', 'max:200'],
            'body'       => ['nullable', 'string', 'max:20000'],
            'prerelease' => ['nullable', 'boolean'],
            'auto_build' => ['nullable', 'boolean'],
        ], [
            'tag.regex' => 'Tag must look like v1.2.3 (optionally with a suffix, e.g. v1.2.3-rc.1).',
        ]);

        try {
            $release = $this->publisher->createRelease(
                tag: $data['tag'],
                name: $data['name'] ?? '',
                body: $data['body'] ?? '',
                prerelease: (bool) ($data['prerelease'] ?? false),
            );

            $build = null;
            if (!empty($data['auto_build'])) {
                $build = $this->publisher->triggerBuild($release['tag']);
            }
        } catch (RequestException $e) {
            return response()->json([
                'message' => $this->friendlyGithubError($e, 'creating release'),
            ], 502);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'release' => $release,
            'build'   => $build,
        ], 201);
    }

    public function status(Request $request): JsonResponse
    {
        $data = $request->validate([
            'run_id' => 'nullable|integer',
            'tag' => 'nullable|string|max:100',
        ]);

        try {
            if (!empty($data['run_id'])) {
                $run = $this->publisher->getRun((int) $data['run_id']);
            } elseif (!empty($data['tag'])) {
                $run = $this->publisher->findRunForTag($data['tag']);
            } else {
                return response()->json(['message' => 'Provide run_id or tag.'], 422);
            }
        } catch (RequestException $e) {
            return response()->json(['message' => 'GitHub API error: ' . $e->getMessage()], 502);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

        return response()->json(['run' => $run]);
    }
}
