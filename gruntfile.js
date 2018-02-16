'use strict';
module.exports = function (grunt) {

	// load all grunt tasks
	require('matchdep').filterDev('grunt-*').forEach(grunt.loadNpmTasks);

	grunt.initConfig({

		pkg: grunt.file.readJSON('package.json'),
		
		// Gulp can't do what I need well enough
		compress: {
			main: {
				options: {
					archive: 'rbm-field-helpers-wrapper.zip'
				},
				files: [
					{expand: true, dot: true, src: ['./rbm-field-helpers-wrapper/**/*.*'], dest: './'}
				]
			}
		}

	});
	
};