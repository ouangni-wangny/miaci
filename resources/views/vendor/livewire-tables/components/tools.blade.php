@aware(['isTailwind','isBootstrap'])

<div {{
    $attributes->merge($this->getToolsAttributes)
        ->class([
            'flex-col bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-4' => $isTailwind && ($this->getToolsAttributes['default-styling'] ?? true),
            'd-flex flex-column' => $isBootstrap && ($this->getToolsAttributes['default-styling'] ?? true)
        ])
        ->except(['default','default-styling','default-colors'])
    }}
>
    {{ $slot }}
</div>
