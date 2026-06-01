import React, { useState, useEffect } from 'react';
import { StyleSheet, View, Text, FlatList, TouchableOpacity, ActivityIndicator, Alert, TextInput, Modal, StatusBar, RefreshControl } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons, MaterialCommunityIcons } from '@expo/vector-icons';
import { router } from 'expo-router';

export default function DashboardScreen() {
  const [leads, setLeads] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [userId, setUserId] = useState<string>('1');

  // Edit Modal State
  const [editModalVisible, setEditModalVisible] = useState(false);
  const [editingLead, setEditingLead] = useState<any>(null);
  const [editFormData, setEditFormData] = useState({ name: '', phone: '', symptoms: '' });

  const API_BASE = 'https://praanveda.net/web/api/leads.php';

  const fetchLeads = async () => {
    try {
      const uid = await AsyncStorage.getItem('userId') || '1';
      setUserId(uid);
      const response = await fetch(`${API_BASE}?action=my_leads&executive_id=${uid}`);
      const result = await response.json();
      if (result.status === 'success') {
        setLeads(result.data);
      }
    } catch (error) {
      console.error(error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const handleLogout = () => {
    Alert.alert(
      'Sign Out',
      'Are you sure you want to sign out?',
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Sign Out',
          style: 'destructive',
          onPress: async () => {
            await AsyncStorage.multiRemove(['userToken', 'userId', 'userRole', 'userName']);
            router.replace('/login');
          },
        },
      ]
    );
  };

  useEffect(() => {
    fetchLeads();
  }, []);

  const onRefresh = () => {
    setRefreshing(true);
    fetchLeads();
  };

  const openEditModal = (lead: any) => {
    setEditingLead(lead);
    setEditFormData({
      name: lead.name,
      phone: lead.phone,
      symptoms: lead.symptoms_notes || ''
    });
    setEditModalVisible(true);
  };

  const submitEdit = async () => {
    try {
      const data = new FormData();
      data.append('patient_id', editingLead.patient_id);
      data.append('name', editFormData.name);
      data.append('phone', editFormData.phone);
      data.append('symptoms_notes', editFormData.symptoms);

      const response = await fetch(`${API_BASE}?action=update_lead`, {
        method: 'POST',
        body: data
      });
      const result = await response.json();
      if (result.status === 'success') {
        Alert.alert('Success', 'Lead updated successfully');
        setEditModalVisible(false);
        fetchLeads();
      } else {
        Alert.alert('Error', result.message);
      }
    } catch (e) {
      Alert.alert('Error', 'Failed to update lead');
    }
  };

  const renderHeader = () => {
    const totalLeads = leads.length;
    const pendingLeads = leads.filter(l => l.status === 'Pending').length;
    const doneLeads = leads.filter(l => l.status === 'Done').length;

    return (
      <>
        <View style={styles.statsContainer}>
          <View style={styles.statCard}>
            <MaterialCommunityIcons name="clipboard-text-multiple-outline" size={24} color="#059669" />
            <Text style={styles.statNumber}>{totalLeads}</Text>
            <Text style={styles.statLabel}>Total Leads</Text>
          </View>
          <View style={styles.statCard}>
            <MaterialCommunityIcons name="clock-outline" size={24} color="#D97706" />
            <Text style={[styles.statNumber, { color: '#D97706' }]}>{pendingLeads}</Text>
            <Text style={[styles.statLabel, { color: '#D97706' }]}>Pending</Text>
          </View>
          <View style={styles.statCard}>
            <MaterialCommunityIcons name="check-decagram-outline" size={24} color="#059669" />
            <Text style={[styles.statNumber, { color: '#059669' }]}>{doneLeads}</Text>
            <Text style={[styles.statLabel, { color: '#059669' }]}>Done</Text>
          </View>
        </View>

        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>Recent Leads</Text>
          <TouchableOpacity onPress={onRefresh}>
            <Ionicons name="reload-circle" size={24} color="#059669" />
          </TouchableOpacity>
        </View>
      </>
    );
  };

  const renderItem = ({ item }: { item: any }) => (
    <View style={styles.card}>
      <View style={styles.cardHeader}>
        <View style={styles.patientInfo}>
          <View style={styles.avatarPlaceholder}>
            <Text style={styles.avatarText}>{item.name ? item.name.charAt(0).toUpperCase() : 'P'}</Text>
          </View>
          <View>
            <Text style={styles.patientName}>{item.name}</Text>
            <Text style={styles.detailsText}>{item.phone} • Age: {item.age}</Text>
          </View>
        </View>
        <View style={[styles.badge, item.status === 'Pending' ? styles.badgePending : styles.badgeDone]}>
          <Text style={[styles.badgeText, item.status === 'Pending' ? styles.badgeTextPending : styles.badgeTextDone]}>{item.status}</Text>
        </View>
      </View>

      <View style={styles.divider} />

      <View style={styles.cardBody}>
        <View style={styles.infoRow}>
          <Ionicons name="medical-outline" size={16} color="#6B7280" />
          <Text style={styles.symptomsText} numberOfLines={2}>
            {item.symptoms_notes || 'No symptoms specified'}
          </Text>
        </View>
        <View style={styles.infoRow}>
          <Ionicons name="calendar-outline" size={16} color="#6B7280" />
          <Text style={styles.dateText}>{new Date(item.created_at).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' })}</Text>
        </View>
      </View>

      {item.status === 'Pending' && (
        <TouchableOpacity style={styles.editBtn} onPress={() => openEditModal(item)}>
          <Ionicons name="create-outline" size={16} color="#2563EB" style={{ marginRight: 6 }} />
          <Text style={styles.editBtnText}>Edit Details</Text>
        </TouchableOpacity>
      )}
    </View>
  );

  if (loading) return <View style={styles.center}><ActivityIndicator size="large" color="#10B981" /></View>;

  return (
    <View style={{ flex: 1, backgroundColor: '#064E3B' }}>
      <SafeAreaView style={styles.container} edges={['top']}>
        <StatusBar barStyle="light-content" backgroundColor="#064E3B" />
        
        <View style={styles.headerWrapper}>
          <View style={styles.topBar}>
            <View>
              <Text style={styles.greeting}>Hello, Executive</Text>
              <Text style={styles.title}>Your Dashboard</Text>
              <View style={styles.roleBadge}>
                <Text style={styles.roleBadgeText}>Lead Management</Text>
              </View>
            </View>
            <TouchableOpacity style={styles.avatarContainer} onPress={handleLogout}>
              <Ionicons name="log-out-outline" size={20} color="#064E3B" />
            </TouchableOpacity>
          </View>
        </View>

        <FlatList
          data={leads}
          keyExtractor={(item) => item.consultation_id?.toString() || Math.random().toString()}
          renderItem={renderItem}
          ListHeaderComponent={renderHeader}
          contentContainerStyle={styles.list}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={['#10B981']} />}
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <Ionicons name="document-text-outline" size={48} color="#D1D5DB" />
              <Text style={styles.emptyText}>You haven't submitted any leads yet.</Text>
              <Text style={styles.emptySubtext}>Pull down to refresh or add a new lead.</Text>
            </View>
          }
        />

      <Modal visible={editModalVisible} animationType="slide" transparent={true}>
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Edit Lead</Text>
              <TouchableOpacity onPress={() => setEditModalVisible(false)}>
                <Ionicons name="close-circle" size={28} color="#9CA3AF" />
              </TouchableOpacity>
            </View>

            <Text style={styles.label}>Patient Name</Text>
            <TextInput style={styles.input} value={editFormData.name} onChangeText={t => setEditFormData(prev => ({ ...prev, name: t }))} />

            <Text style={styles.label}>Phone Number</Text>
            <TextInput style={styles.input} value={editFormData.phone} keyboardType="phone-pad" onChangeText={t => setEditFormData(prev => ({ ...prev, phone: t }))} />

            <Text style={styles.label}>Symptoms</Text>
            <TextInput style={[styles.input, { height: 100, textAlignVertical: 'top' }]} multiline value={editFormData.symptoms} onChangeText={t => setEditFormData(prev => ({ ...prev, symptoms: t }))} />

            <View style={styles.modalActions}>
              <TouchableOpacity style={styles.cancelBtn} onPress={() => setEditModalVisible(false)}>
                <Text style={styles.cancelBtnText}>Cancel</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.saveBtn} onPress={submitEdit}>
                <Ionicons name="checkmark-circle-outline" size={20} color="#fff" style={{ marginRight: 6 }} />
                <Text style={styles.saveBtnText}>Save Changes</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
      </SafeAreaView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F0FDF4' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#F0FDF4' },

  // Header Styles
  headerWrapper: {
    backgroundColor: '#064E3B',
    paddingHorizontal: 20,
    paddingBottom: 20,
    borderBottomLeftRadius: 24,
    borderBottomRightRadius: 24,
    elevation: 0,
    zIndex: 10,
    marginTop: -50,
    paddingTop: 50 + 16,
  },
  topBar: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  greeting: { fontSize: 13, color: '#A7F3D0', fontWeight: '500' },
  title: { fontSize: 22, fontWeight: '800', color: '#FFFFFF', letterSpacing: -0.5, marginTop: 2 },
  roleBadge: { backgroundColor: 'rgba(255,255,255,0.2)', paddingHorizontal: 8, paddingVertical: 4, borderRadius: 6, alignSelf: 'flex-start', marginTop: 6 },
  roleBadgeText: { fontSize: 10, fontWeight: '700', color: '#ECFDF5', textTransform: 'uppercase' },
  avatarContainer: { width: 36, height: 36, borderRadius: 18, backgroundColor: '#D1FAE5', justifyContent: 'center', alignItems: 'center' },

  // Stats Styles
  statsContainer: { flexDirection: 'row', flexWrap: 'wrap', marginTop: 20, paddingHorizontal: 16, marginBottom: 20 },
  statCard: { flex: 1, minWidth: '30%', backgroundColor: '#FFFFFF', borderRadius: 16, paddingVertical: 12, paddingHorizontal: 8, alignItems: 'center', margin: 4, elevation: 3, shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 5, shadowOffset: { width: 0, height: 2 } },
  statNumber: { fontSize: 22, fontWeight: '800', color: '#064E3B', marginVertical: 4 },
  statLabel: { fontSize: 10, fontWeight: '700', color: '#64748B', textTransform: 'uppercase' },

  // List Styles
  sectionHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 20, marginBottom: 12 },
  sectionTitle: { fontSize: 16, fontWeight: '800', color: '#064E3B' },
  list: { paddingBottom: 40 },

  // Card Styles
  card: { backgroundColor: '#FFFFFF', borderRadius: 16, padding: 16, marginHorizontal: 16, marginBottom: 12, elevation: 2, shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 5, shadowOffset: { width: 0, height: 2 } },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingBottom: 12 },
  patientInfo: { flexDirection: 'row', alignItems: 'center', flex: 1 },
  avatarPlaceholder: { width: 40, height: 40, borderRadius: 20, backgroundColor: '#D1FAE5', justifyContent: 'center', alignItems: 'center', marginRight: 12 },
  avatarText: { fontSize: 16, fontWeight: '800', color: '#059669' },
  patientName: { fontSize: 15, fontWeight: '700', color: '#0F172A', marginBottom: 2 },
  detailsText: { fontSize: 12, color: '#64748B', fontWeight: '500' },
  badge: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: 8, marginLeft: 8 },
  badgePending: { backgroundColor: '#FEF3C7' },
  badgeDone: { backgroundColor: '#D1FAE5' },
  badgeText: { fontSize: 11, fontWeight: '700' },
  badgeTextPending: { color: '#B45309' },
  badgeTextDone: { color: '#047857' },

  divider: { height: 1, backgroundColor: '#F1F5F9' },

  cardBody: { paddingTop: 12 },
  infoRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 8 },
  symptomsText: { fontSize: 13, color: '#334155', marginLeft: 8, flex: 1 },
  dateText: { fontSize: 12, color: '#64748B', marginLeft: 8, fontWeight: '500' },

  editBtn: { flexDirection: 'row', backgroundColor: '#F0FDF4', padding: 12, borderRadius: 12, alignItems: 'center', justifyContent: 'center', marginTop: 10, borderWidth: 1, borderColor: '#D1FAE5' },
  editBtnText: { color: '#059669', fontWeight: '700', fontSize: 13 },

  emptyContainer: { alignItems: 'center', justifyContent: 'center', paddingVertical: 60 },
  emptyText: { marginTop: 12, color: '#475569', fontSize: 16, fontWeight: '700' },
  emptySubtext: { marginTop: 4, color: '#94A3B8', fontSize: 12 },

  // Modal Styles
  modalOverlay: { flex: 1, backgroundColor: 'rgba(17, 24, 39, 0.6)', justifyContent: 'flex-end' },
  modalContent: { backgroundColor: '#fff', padding: 24, borderTopLeftRadius: 24, borderTopRightRadius: 24, shadowColor: '#000', shadowOffset: { width: 0, height: -4 }, shadowOpacity: 0.1, shadowRadius: 12, elevation: 10 },
  modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20 },
  modalTitle: { fontSize: 22, fontWeight: 'bold', color: '#111827' },
  label: { fontSize: 14, color: '#4B5563', marginBottom: 6, fontWeight: '600' },
  input: { borderWidth: 1, borderColor: '#E5E7EB', borderRadius: 12, padding: 14, marginBottom: 16, backgroundColor: '#F9FAFB', fontSize: 16, color: '#1F2937' },
  modalActions: { flexDirection: 'row', justifyContent: 'flex-end', marginTop: 12 },
  cancelBtn: { padding: 14, marginRight: 8, borderRadius: 12, justifyContent: 'center' },
  cancelBtnText: { color: '#6B7280', fontWeight: 'bold', fontSize: 16 },
  saveBtn: { flexDirection: 'row', backgroundColor: '#059669', paddingVertical: 14, paddingHorizontal: 20, borderRadius: 12, alignItems: 'center', elevation: 2, shadowColor: '#059669', shadowOpacity: 0.3, shadowRadius: 4, shadowOffset: { width: 0, height: 2 } },
  saveBtnText: { color: '#fff', fontWeight: 'bold', fontSize: 16 }
});
