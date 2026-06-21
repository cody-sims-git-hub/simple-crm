<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pipeline stage presentation
    |--------------------------------------------------------------------------
    |
    | Single source of truth for how each pipeline stage is shown across the UI
    | (the Records status pills + legend and the Analytics "Leads by stage"
    | panel). Colors map to the brand theme tokens; keep this restrained —
    | New = attention (amber), Closed = won (green), blue stays dominant.
    |
    */

    'statusStyles' => [
        'New' => 'bg-warning/10 text-warning border border-warning/40',
        'Contacted' => 'bg-accent-muted text-accent border border-accent/40',
        'Quoted' => 'bg-info/10 text-info border border-info/40',
        'Submitted' => 'bg-surface-raised text-slate border border-hairline',
        'Closed' => 'bg-success/10 text-success border border-success/40',
    ],

    'statusMeaning' => [
        'New' => 'Needs action',
        'Contacted' => 'Engaged',
        'Quoted' => 'Offer out',
        'Submitted' => 'Awaiting',
        'Closed' => 'Won',
    ],

];
