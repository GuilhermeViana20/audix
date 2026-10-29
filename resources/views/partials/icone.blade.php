<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($icone)
@case('inicio')<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>@break
@case('temperatura')<path d="M9 14.5V5a3 3 0 0 1 6 0v9.5a5 5 0 1 1-6 0Z"/><path d="M12 8v9m6-11h3m-3 4h2"/><circle cx="12" cy="18" r="1"/>@break
@case('higiene')<path d="M12 3S5 11 5 15a7 7 0 0 0 14 0c0-4-7-12-7-12Z"/><path d="M8 15a4 4 0 0 0 4 4"/>@break
@case('pragas')<rect x="8" y="7" width="8" height="13" rx="4"/><path d="m9 3 3 4 3-4M3 9l5 2m8 0 5-2M3 15h5m8 0h5M5 22l4-4m6 0 4 4M12 8v12"/>@break
@default<path d="M3 10h18L19 3H5l-2 7Zm1 0v11h16V10M9 21v-7h6v7M3 10a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0"/>
@endswitch
</svg>
