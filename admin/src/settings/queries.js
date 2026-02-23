import React, {useState} from "react"
import apiClient from './http-common.js';
import {useQuery, useMutation, useQueryClient} from 'react-query';

export function useOptions(tab = '') {
    function fetchOptions(queryobj) {
        const queryjoin = (rsvpmaker_rest.rest_url.includes('?')) ? '&' : '?';
        return apiClient.get('rsvp_options'+queryjoin+'tab='+tab);
    }
    return useQuery(['rsvp_options'], fetchOptions, { enabled: true, retry: 2, onSuccess: (data, error, variables, context) => {
        console.log('rsvp options query',data);
    }, onError: (err, variables, context) => {
       console.log('error retrieving rsvp options',err);
      }, refetchInterval: false });
}

export function useOptionsMutation(setChanges,makeNotification) {
    const queryClient = useQueryClient();

    async function updateOption (option) {
        return await apiClient.post('rsvp_options', option);
    }
    
    return useMutation(updateOption, {
        onMutate: async (options) => {
            console.log('optimistic update option',options);
            await queryClient.cancelQueries(['rsvp_options']);
            const previousValue = queryClient.getQueryData(['rsvp_options']);
            queryClient.setQueryData(['rsvp_options'],(oldQueryData) => {
                //function passed to setQueryData
                const {data} = oldQueryData;
                options.forEach((o) => {
                    if('rsvp_options' == o.type)
                        data.rsvp_options[o.key] = o.value;
                });
                const newdata = {
                    ...oldQueryData, data: data
                };
                console.log('newdata optimistic update',newdata);
                return newdata;
            }) 
            //makeNotification('Updating ...');
            console.log('updating options');
            return {previousValue}
        },
        onSettled: (data, error, variables, context) => {
            queryClient.invalidateQueries(['rsvp_options']);
        },
        onSuccess: (data, error, variables, context) => {
            console.log('updated');
            setChanges([]);
        },
        onError: (err, variables, context) => {
            //makeNotification('Error '+err.message);
            console.log('update options error',err);
            queryClient.setQueryData("rsvp_options", context.previousValue);
        },    
    }
)
}

export function useRSVPDate(eventID) {
    function fetchRSVPDate(queryobj) {
        const queryjoin = (rsvpmaker_rest.rest_url.includes('?')) ? '&' : '?';
        return apiClient.get('rsvp_event_date'+queryjoin+'event_id='+eventID);
    }
    return useQuery(['rsvp_event_date'], fetchRSVPDate, { enabled: true, retry: 2, onSuccess: (data, error, variables, context) => {
        console.log('rsvp_event_date query',data);
    }, onError: (err, variables, context) => {
       console.log('error retrieving rsvp_event_date',err);
      }, refetchInterval: false });
}

export function useCopyDefaults() {
    function fetchCopyDefaults(queryobj) {
        return apiClient.get('copy_defaults');
    }
    return useQuery([], fetchCopyDefaults, { enabled: true, retry: 2, onSuccess: (data, error, variables, context) => {
        alert('Copied to '+data.data.updated.substring(0,200));
        console.log('copy defaults',data);
    }, onError: (err, variables, context) => {
       console.log('error copy defaults',err);
      }, refetchInterval: false });
}

export function useRSVPDateMutation(eventID) {
    const queryClient = useQueryClient();
    console.log('useRSVPDateMutation called with');
    console.log('useRSVPDateMutation queryClient',queryClient);

    async function updateDate (update) {
        const queryjoin = (rsvpmaker_rest.rest_url.includes('?')) ? '&' : '?';
        return await apiClient.post('rsvp_event_date'+queryjoin+'event_id='+eventID, update);
    }
    
    return useMutation(updateDate, {
        onMutate: async (update) => {
            console.log('optimistic update event',update);
            await queryClient.cancelQueries(['rsvp_event_date']);
            const previousValue = queryClient.getQueryData(['rsvp_event_date']);
            console.log('previousValue',previousValue);
            queryClient.setQueryData(['rsvp_event_date'],(oldQueryData) => {
                const {data} = oldQueryData;
                if(update.date)
                    data.date = update.date;
                if(update.enddate)
                    data.enddate = update.enddate;
                if(update.display_type && update.display_type != null)
                    data.display_type = update.display_type;
                if(update.timezone)
                    data.timezone = update.timezone;
                if(update.metaKey)
                    data.meta[update.metaKey] = update.metaValue;
                const newdata = {
                    data: data
                };
                console.log('newdata optimistic update',newdata);
                return newdata;
            }) 
            console.log('updating options');
            return {previousValue}
        },
        onSettled: (data, error, variables, context) => {
            queryClient.invalidateQueries(['rsvp_event_date']);
        },
        onSuccess: (data, error, variables, context) => {
            
            console.log('updated',data);
            queryClient.setQueryData(['rsvp_event_date'],data);
            queryClient.invalidateQueries(['rsvp_event_date']);
        },
        onError: (err, variables, context) => {
            console.log('update dates error',err);
        },    
    }
)
}
