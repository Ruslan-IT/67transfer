@if ($icon === 'phone')
    <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round">
        <path d="M8 4.5h2.2l1 2.8-1.7 1a11 11 0 0 0 5.2 5.2l1-1.7 2.8 1V17a1.5 1.5 0 0 1-1.6 1.5A13.5 13.5 0 0 1 5.5 8.1 1.5 1.5 0 0 1 7 6.5h1z"></path>
    </svg>
@elseif ($icon === 'people')
    <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round">
        <circle cx="8" cy="9" r="2"></circle>
        <circle cx="16" cy="9" r="2"></circle>
        <circle cx="12" cy="7.5" r="2.1"></circle>
        <path d="M4.8 18.2c.5-2 2-3.1 3.2-3.1s2.7 1.1 3.2 3.1M12.8 18.2c.5-2 2-3.1 3.2-3.1s2.7 1.1 3.2 3.1"></path>
    </svg>
@else
    <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round">
        <rect x="6" y="4.5" width="12" height="15" rx="2"></rect>
        <path d="M9 4.5h6v2.2H9zM9 12h6M9 15.5h4"></path>
    </svg>
@endif
