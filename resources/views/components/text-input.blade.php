@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full rounded-2xl border-2 border-slate-100 focus:border-indigo-400 focus:ring-0 bg-slate-50 px-4 py-3 text-sm text-slate-800']) }}>
