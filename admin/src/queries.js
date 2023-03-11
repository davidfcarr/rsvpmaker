import React, {useState} from "react"
import apiClient from './http-common.js';
import {useQuery, useMutation, useQueryClient} from 'react-query';

export function useOptions() {
    function fetchOptions(queryobj) {
        return apiClient.get('rsvp_options');
    }
    return useQuery(['rsvp_options'], fetchOptions, { enabled: true, retry: 2, onSuccess: (data, error, variables, context) => {
        console.log('rsvp options query',data);
    }, onError: (err, variables, context) => {
       console.log('error retrieving rsvp options',err);
      }, refetchInterval: false });
}

export function useOptionsMutation(updateSuccess) {
    const queryClient = useQueryClient();

    async function updateOption (option) {
        return await apiClient.post('rsvp_options', option);
    }
    
    return useMutation(updateOption, {
        onMutate: async (option) => {
            console.log('optimistic update option',option);
            await queryClient.cancelQueries(['rsvp_options']);
            const previousValue = queryClient.getQueryData(['rsvp_options']);
            queryClient.setQueryData(['rsvp_options'],(oldQueryData) => {
                //function passed to setQueryData
                const {data} = oldQueryData;
                if(Array.isArray(option)) {
                    option.forEach((o) => {data[o.key] = o.value;});
                }
                else
                    data[option.key] = option.value;
                const newdata = {
                    ...oldQueryData, data: data
                };
                console.log('key optimistic update',option.key);
                console.log('value optimistic update',option.value);
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
            updateSuccess();
            //makeNotification('Updated');
        },
        onError: (err, variables, context) => {
            //makeNotification('Error '+err.message);
            console.log('update options error',err);
            queryClient.setQueryData("rsvp_options", context.previousValue);
        },    
    }
)
}
