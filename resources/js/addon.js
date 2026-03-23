import Export from './components/Export.vue';

Statamic.booting(() => {
    Statamic.$inertia.register('statamic-export::export.utility', Export);
});
