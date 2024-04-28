import { startStimulusApp } from '@symfony/stimulus-bridge';

// Example of initializing Stimulus with Symfony
const application = startStimulusApp(require.context('./controllers', true, /\.js$/));

// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
