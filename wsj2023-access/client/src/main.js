import App from './App.svelte';

const app = new App({
	target: document.body,
	props: {
		type: 'IST'
	}
});

export default app;