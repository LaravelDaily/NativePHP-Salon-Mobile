{{-- A greyed-out bar standing in for text, an avatar or a button that has not
     arrived yet. `$class` carries the size and shape, e.g. 'w-[220] h-[34]'.

     There is no shimmer: EDGE's Tailwind parser has no animation utilities, so
     these are deliberately still rather than faked with a poll-driven redraw. --}}
<column class="bg-theme-surface-variant rounded-full {{ $class }}"></column>
