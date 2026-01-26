<?php

namespace Bow\Database;

use ArrayAccess;
use Bow\Database\Collection as DatabaseCollection;
use Bow\Support\Collection as SupportCollection;
use Countable;
use IteratorAggregate;
use Traversable;

class Pagination implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * The base URL for pagination links.
     *
     * @var string|null
     */
    private ?string $baseUrl = null;

    /**
     * The query string parameters to append to pagination URLs.
     *
     * @var array
     */
    private array $queryParams = [];

    /**
     * The page query parameter name.
     *
     * @var string
     */
    private string $pageParam = 'page';

    /**
     * Pagination constructor.
     *
     * @param int                                  $next     The next page number.
     * @param int                                  $previous The previous page number.
     * @param int                                  $total    The total number of items.
     * @param int                                  $perPage  The number of items per page.
     * @param int                                  $current  The current page number.
     * @param SupportCollection|DatabaseCollection $data     The collection of items for the current page.
     */
    public function __construct(
        private readonly int $next,
        private readonly int $previous,
        private readonly int $total,
        private readonly int $perPage,
        private readonly int $current,
        private readonly SupportCollection|DatabaseCollection $data
    ) {
    }

    /**
     * Set the base URL for pagination links.
     *
     * @param string $url
     * @return static
     */
    public function setBaseUrl(string $url): static
    {
        $this->baseUrl = $url;

        return $this;
    }

    /**
     * Get the base URL for pagination links.
     *
     * @return string|null
     */
    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    /**
     * Set the page query parameter name.
     *
     * @param string $name
     * @return static
     */
    public function setPageParam(string $name): static
    {
        $this->pageParam = $name;

        return $this;
    }

    /**
     * Get the page query parameter name.
     *
     * @return string
     */
    public function getPageParam(): string
    {
        return $this->pageParam;
    }

    /**
     * Add query parameters to the pagination URLs.
     *
     * @param array $params
     * @return static
     */
    public function withQueryParams(array $params): static
    {
        $this->queryParams = array_merge($this->queryParams, $params);

        return $this;
    }

    /**
     * Set query parameters for the pagination URLs (replaces existing).
     *
     * @param array $params
     * @return static
     */
    public function setQueryParams(array $params): static
    {
        $this->queryParams = $params;

        return $this;
    }

    /**
     * Get the query parameters.
     *
     * @return array
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * Get the next page number.
     *
     * @return int
     */
    public function next(): int
    {
        return $this->next;
    }

    /**
     * Check if there is a next page.
     *
     * @return bool
     */
    public function hasNext(): bool
    {
        return $this->next != 0;
    }

    /**
     * Get the number of items per page.
     *
     * @return int
     */
    public function perPage(): int
    {
        return $this->perPage;
    }

    /**
     * Get the previous page number.
     *
     * @return int
     */
    public function previous(): int
    {
        return $this->previous;
    }

    /**
     * Check if there is a previous page.
     *
     * @return bool
     */
    public function hasPrevious(): bool
    {
        return $this->previous != 0;
    }

    /**
     * Get the current page number.
     *
     * @return int
     */
    public function current(): int
    {
        return $this->current;
    }

    /**
     * Get the collection of items for the current page.
     *
     * @return SupportCollection|DatabaseCollection
     */
    public function items(): SupportCollection|DatabaseCollection
    {
        return $this->data;
    }

    /**
     * Get the total number of items.
     *
     * @return int
     */
    public function total(): int
    {
        return $this->total;
    }

    /**
     * Get the total number of pages.
     *
     * @return int
     */
    public function totalPages(): int
    {
        return (int) ceil($this->total / $this->perPage);
    }

    /**
     * Check if there are multiple pages.
     *
     * @return bool
     */
    public function hasPages(): bool
    {
        return $this->totalPages() > 1;
    }

    /**
     * Check if currently on the first page.
     *
     * @return bool
     */
    public function onFirstPage(): bool
    {
        return $this->current === 1;
    }

    /**
     * Check if currently on the last page.
     *
     * @return bool
     */
    public function onLastPage(): bool
    {
        return $this->current === $this->totalPages();
    }

    /**
     * Check if the pagination has no items.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->data->isEmpty();
    }

    /**
     * Check if the pagination has items.
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Get the number of items on the current page.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->data->count();
    }

    /**
     * Get the "index" of the first item being paginated (1-indexed).
     *
     * @return int
     */
    public function firstItem(): int
    {
        if ($this->total === 0) {
            return 0;
        }

        return ($this->current - 1) * $this->perPage + 1;
    }

    /**
     * Get the "index" of the last item being paginated (1-indexed).
     *
     * @return int
     */
    public function lastItem(): int
    {
        if ($this->total === 0) {
            return 0;
        }

        return $this->firstItem() + $this->count() - 1;
    }

    /**
     * Get the pagination data as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'current_page' => $this->current,
            'data' => $this->data->toArray(),
            'first_item' => $this->firstItem(),
            'last_item' => $this->lastItem(),
            'per_page' => $this->perPage,
            'total' => $this->total,
            'total_pages' => $this->totalPages(),
            'next_page' => $this->hasNext() ? $this->next : null,
            'previous_page' => $this->hasPrevious() ? $this->previous : null,
        ];
    }

    /**
     * Convert the pagination to JSON.
     *
     * @param int $options
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Build a URL for a specific page number.
     *
     * @param int $page
     * @return string|null
     */
    public function url(int $page): ?string
    {
        if ($this->baseUrl === null) {
            return null;
        }

        if ($page < 1 || $page > $this->totalPages()) {
            return null;
        }

        $params = array_merge($this->queryParams, [$this->pageParam => $page]);

        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        $separator = str_contains($this->baseUrl, '?') ? '&' : '?';

        return $this->baseUrl . $separator . $query;
    }

    /**
     * Get the URL for the next page.
     *
     * @return string|null
     */
    public function nextPageUrl(): ?string
    {
        if (!$this->hasNext()) {
            return null;
        }

        return $this->url($this->next);
    }

    /**
     * Get the URL for the previous page.
     *
     * @return string|null
     */
    public function previousPageUrl(): ?string
    {
        if (!$this->hasPrevious()) {
            return null;
        }

        return $this->url($this->previous);
    }

    /**
     * Get the URL for the first page.
     *
     * @return string|null
     */
    public function firstPageUrl(): ?string
    {
        return $this->url(1);
    }

    /**
     * Get the URL for the last page.
     *
     * @return string|null
     */
    public function lastPageUrl(): ?string
    {
        return $this->url($this->totalPages());
    }

    /**
     * Get an array of URLs for a range of pages.
     *
     * @param int $onEachSide Number of links on each side of current page
     * @return array
     */
    public function getUrlRange(int $onEachSide = 3): array
    {
        $totalPages = $this->totalPages();
        $current = $this->current;

        $start = max(1, $current - $onEachSide);
        $end = min($totalPages, $current + $onEachSide);

        $urls = [];
        for ($page = $start; $page <= $end; $page++) {
            $urls[$page] = $this->url($page);
        }

        return $urls;
    }

    /**
     * Get pagination links data for rendering.
     *
     * @param int $onEachSide Number of links on each side of current page
     * @return array
     */
    public function links(int $onEachSide = 3): array
    {
        $totalPages = $this->totalPages();
        $current = $this->current;

        $links = [];

        // Previous link
        $links[] = [
            'url' => $this->previousPageUrl(),
            'label' => '&laquo; Previous',
            'active' => false,
            'disabled' => !$this->hasPrevious(),
        ];

        // Page number links
        $start = max(1, $current - $onEachSide);
        $end = min($totalPages, $current + $onEachSide);

        // Add first page and ellipsis if needed
        if ($start > 1) {
            $links[] = [
                'url' => $this->url(1),
                'label' => '1',
                'active' => false,
                'disabled' => false,
            ];

            if ($start > 2) {
                $links[] = [
                    'url' => null,
                    'label' => '...',
                    'active' => false,
                    'disabled' => true,
                ];
            }
        }

        // Add page links
        for ($page = $start; $page <= $end; $page++) {
            $links[] = [
                'url' => $this->url($page),
                'label' => (string) $page,
                'active' => $page === $current,
                'disabled' => false,
            ];
        }

        // Add ellipsis and last page if needed
        if ($end < $totalPages) {
            if ($end < $totalPages - 1) {
                $links[] = [
                    'url' => null,
                    'label' => '...',
                    'active' => false,
                    'disabled' => true,
                ];
            }

            $links[] = [
                'url' => $this->url($totalPages),
                'label' => (string) $totalPages,
                'active' => false,
                'disabled' => false,
            ];
        }

        // Next link
        $links[] = [
            'url' => $this->nextPageUrl(),
            'label' => 'Next &raquo;',
            'active' => false,
            'disabled' => !$this->hasNext(),
        ];

        return $links;
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->data->offsetExists($offset);
    }

    /**
     * Get an item at a given offset.
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->data->offsetGet($offset);
    }

    /**
     * Set the item at a given offset.
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data->offsetSet($offset, $value);
    }

    /**
     * Unset the item at a given offset.
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->data->offsetUnset($offset);
    }

    /**
     * Get an iterator for the items.
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return $this->data->getIterator();
    }
}
