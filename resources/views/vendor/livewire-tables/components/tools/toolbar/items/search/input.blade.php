@aware(['isTailwind', 'isBootstrap'])
<input
    id="{{ $this->getTableName }}-search"
    wire:model{{ $this->getSearchOptions() }}="search"
    placeholder="{{ $this->getSearchPlaceholder() }}"
    type="text"
    {{ 
        $attributes->merge($this->getSearchFieldAttributes())
        ->class([
            'rounded-xl shadow-sm transition duration-150 ease-in-out sm:text-sm sm:leading-5 rounded-none rounded-l-xl focus:ring-0 focus:border-gray-300' => $isTailwind && $this->hasSearch() && (($this->getSearchFieldAttributes()['default'] ?? true) || ($this->getSearchFieldAttributes()['default-styling'] ?? true)),
            'rounded-xl shadow-sm transition duration-150 ease-in-out sm:text-sm sm:leading-5 rounded-xl focus:ring focus:ring-opacity-50' => $isTailwind && !$this->hasSearch()  && (($this->getSearchFieldAttributes()['default'] ?? true) || ($this->getSearchFieldAttributes()['default-styling'] ?? true)),
            'border-gray-300 dark:bg-gray-700 dark:text-white dark:border-gray-600 focus:border-gray-300' => $isTailwind && $this->hasSearch()  && (($this->getSearchFieldAttributes()['default'] ?? true) || ($this->getSearchFieldAttributes()['default-colors'] ?? true)),
            'border-gray-300 dark:bg-gray-700 dark:text-white dark:border-gray-600 focus:border-primary-300 focus:ring-primary-200' => $isTailwind && !$this->hasSearch()  && (($this->getSearchFieldAttributes()['default'] ?? true) || ($this->getSearchFieldAttributes()['default-colors'] ?? true)),
            'block w-full py-2.5' => !$this->hasSearchIcon,
            'pl-8 pr-4' => $this->hasSearchIcon,
            'form-control' => $isBootstrap && ($this->getSearchFieldAttributes()['default'] ?? true),
        ])
        ->except(['default','default-styling','default-colors']) 
    }}

/>