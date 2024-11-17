<div
    id="{{ $record->getKey() }}"
    wire:click="recordClicked('{{ $record->getKey() }}', {{ @json_encode($record) }})"
    class="record bg-white dark:bg-gray-700 rounded-lg px-4 py-2 cursor-grab font-medium text-gray-600 dark:text-gray-200 flex flex-row"
    @if($record->timestamps && now()->diffInSeconds($record->{$record::UPDATED_AT}) < 3)
        x-data
        x-init="
            $el.classList.add('animate-pulse-twice', 'bg-primary-100', 'dark:bg-primary-800')
            $el.classList.remove('bg-white', 'dark:bg-gray-700')
            setTimeout(() => {
                $el.classList.remove('bg-primary-100', 'dark:bg-primary-800')
                $el.classList.add('bg-white', 'dark:bg-gray-700')
            }, 3000)
        "
    @endif
>
     
      
    <div>
        <div>Service: {{ $record->additional_data['services'] ?? 'N/A' }}</div>
        
      <div>Reservation ID: {{ $record->{static::$recordTitleAttribute} }}</div>
        
        @if($record->additional_data['status'] === 'done')
            <div>End Time: {{ $record->additional_data['end_times'] ?? 'N/A' }}</div>
        @else
           <div></div>
        @endif
        
        <div class="relative mt-2">
           <div class="h-3 bg-gray-400 rounded-full"></div>
           <div class="absolute h-3 bg-primary-500 rounded-full top-0" style="width: {{ $record->additional_data['progress'] }}%;"></div>
        </div>
  
  </div>
   <div class="text-gray-400 w-1/4 h-full">{{ $record->additional_data['names'] ?? 'N/A' }}</div>
      

</div>
