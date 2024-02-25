import Export from './Components/Export.vue';

Statamic.booting(() => {
    Statamic.$components.register('export', Export);
});
