{{-- Brand theme tokens — palette extracted from simsdigitalpartners.com.
     Shared by the app layout and the standalone auth pages so every screen
     references the same theme (bg-canvas, text-accent, etc.) instead of
     hardcoding colors. --}}
<style type="text/tailwindcss">
    @theme {
        /* Surfaces — deep navy page, lighter navy cards, raised controls */
        --color-canvas: #060B14;
        --color-surface: #0C1320;
        --color-surface-raised: #18222F;
        --color-surface-hover: #212C3B;

        /* Slate borders */
        --color-hairline: #1E2835;
        --color-hairline-strong: #2A3645;

        /* Electric blue primary + soft glow accents */
        --color-accent: #1983FB;
        --color-accent-hover: #3D97FC;
        --color-accent-muted: #0E2740;

        /* White / slate text */
        --color-ink: #F6F9FC;
        --color-slate-light: #CBD5E1;
        --color-slate: #94A3B8;
        --color-slate-dim: #64748B;

        /* Secondary palette — the ONLY non-blue accents, used sparingly to
           signal meaning (never decoration). Blue stays the dominant accent. */
        --color-success: #34D399;   /* won / delivered / positive */
        --color-warning: #F59E0B;   /* pending / caution */
        --color-danger:  #FB7185;   /* failed / destructive */
        --color-info:    #A78BFA;   /* mid-pipeline (violet) */

        /* Soft blue glow for primary actions */
        --shadow-glow: 0 1px 2px 0 rgb(0 0 0 / 0.3), 0 0 0 1px color-mix(in srgb, #1983FB 20%, transparent), 0 12px 36px -12px color-mix(in srgb, #1983FB 45%, transparent);
    }
</style>
